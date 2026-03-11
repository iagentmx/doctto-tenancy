<?php

namespace Tests\Unit\Modules\EspoCrmTenantIngestion;

use App\Exceptions\ApiServiceException;
use App\Exceptions\EspoCrmWebhookException;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantLocation;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmClientInterface;
use App\Modules\EspoCrmTenantIngestion\Services\EspoCrmService;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\ReplaceStaffSchedulesUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertServiceCategoryUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertServiceUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertStaffUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertTenantFromAccountUseCase;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class EspoCrmServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        DB::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_handle_account_updated_requires_an_id(): void
    {
        $service = $this->makeService();

        try {
            $service->handleAccountUpdated([]);
            $this->fail('Se esperaba una excepción.');
        } catch (EspoCrmWebhookException $exception) {
            $this->assertSame('Payload inválido: falta "id" para account-updated.', $exception->getMessage());
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_handle_account_updated_ignores_events_for_unknown_tenants(): void
    {
        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('acc-1')
            ->andReturn(null);

        $service = $this->makeService(tenantRepository: $tenantRepository);

        $result = $service->handleAccountUpdated(['id' => ' acc-1 ']);

        $this->assertSame('success', $result['status']);
        $this->assertSame('Tenant no existe para este account-updated. Evento ignorado.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_handle_account_updated_refreshes_the_account_and_updates_the_existing_tenant(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = new Tenant(['id' => 15]);
        $tenant->id = 15;

        $updatedTenant = Mockery::mock(Tenant::class)->makePartial();
        $updatedTenant->id = 15;
        $updatedTenant->name = 'Tenant actualizado';
        $updatedTenant->shouldReceive('load')->once()->with('primaryLocation')->andReturnSelf();

        $lookupRepository = Mockery::mock(TenantRepositoryInterface::class);
        $lookupRepository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('acc-1')
            ->andReturn($tenant);

        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getAccountById')
            ->once()
            ->with('acc-1')
            ->andReturn(['id' => 'acc-1', 'name' => 'Tenant actualizado']);

        $upsertTenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $upsertTenantRepository->shouldReceive('updateTenant')
            ->once()
            ->with(15, Mockery::on(fn (array $data): bool => $data['name'] === 'Tenant actualizado'))
            ->andReturn($updatedTenant);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('updateOrCreatePrimaryTenantLocation')
            ->once()
            ->with(15, Mockery::type('array'))
            ->andReturn(new TenantLocation());

        $service = $this->makeService(
            client: $client,
            tenantRepository: $lookupRepository,
            upsertTenant: new UpsertTenantFromAccountUseCase($upsertTenantRepository, $locationRepository),
        );

        $result = $service->handleAccountUpdated(['id' => 'acc-1']);

        $this->assertSame('Tenant actualizado correctamente.', $result['message']);
        $this->assertSame('Tenant actualizado', $result['data']['name']);
    }

    public function test_handle_account_updated_remaps_api_service_exceptions(): void
    {
        $tenant = new Tenant(['id' => 15]);
        $tenant->id = 15;

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->andReturn($tenant);

        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getAccountById')
            ->once()
            ->andThrow(new ApiServiceException('Fallo remoto', 409));

        $service = $this->makeService(client: $client, tenantRepository: $tenantRepository);

        try {
            $service->handleAccountUpdated(['id' => 'acc-1']);
            $this->fail('Se esperaba una excepción.');
        } catch (EspoCrmWebhookException $exception) {
            $this->assertSame('Fallo remoto', $exception->getMessage());
            $this->assertSame(409, $exception->getStatusCode());
        }
    }

    public function test_handle_opportunity_updated_ignores_non_closed_won_stages(): void
    {
        $service = $this->makeService();

        $result = $service->handleOpportunityUpdated([
            'id' => 'opp-1',
            'stage' => 'Prospecting',
        ]);

        $this->assertSame('Opportunity no está en stage "Closed Won". Evento ignorado.', $result['message']);
        $this->assertNull($result['data']);
    }

    public function test_handle_opportunity_updated_requires_account_id_in_the_remote_payload(): void
    {
        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getOpportunityById')
            ->once()
            ->with('opp-1')
            ->andReturn(['id' => 'opp-1']);

        $service = $this->makeService(client: $client);

        try {
            $service->handleOpportunityUpdated([
                'id' => 'opp-1',
                'stage' => 'Closed Won',
            ]);
            $this->fail('Se esperaba una excepción.');
        } catch (EspoCrmWebhookException $exception) {
            $this->assertSame('Opportunity inválida: falta "accountId".', $exception->getMessage());
            $this->assertSame(422, $exception->getStatusCode());
        }
    }

    public function test_handle_opportunity_updated_fetches_opportunity_and_account_then_upserts_tenant(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $createdTenant = Mockery::mock(Tenant::class)->makePartial();
        $createdTenant->id = 80;
        $createdTenant->name = 'Tenant nuevo';
        $createdTenant->shouldReceive('load')->once()->with('primaryLocation')->andReturnSelf();

        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getOpportunityById')
            ->once()
            ->with('opp-1')
            ->andReturn(['id' => 'opp-1', 'accountId' => 'acc-99']);
        $client->shouldReceive('getAccountById')
            ->once()
            ->with('acc-99')
            ->andReturn(['id' => 'acc-99', 'name' => 'Tenant nuevo']);

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('updateOrCreateTenant')
            ->once()
            ->with(['espocrm_id' => 'acc-99'], Mockery::on(fn (array $data): bool => $data['name'] === 'Tenant nuevo'))
            ->andReturn($createdTenant);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('updateOrCreatePrimaryTenantLocation')
            ->once()
            ->with(80, Mockery::type('array'))
            ->andReturn(new TenantLocation());

        $service = $this->makeService(
            client: $client,
            upsertTenant: new UpsertTenantFromAccountUseCase($tenantRepository, $locationRepository),
        );

        $result = $service->handleOpportunityUpdated([
            'id' => 'opp-1',
            'stage' => 'Closed Won',
        ]);

        $this->assertSame('Tenant registrado o actualizado correctamente.', $result['message']);
        $this->assertSame(80, $result['data']['id']);
    }

    public function test_handle_service_updated_reloads_the_service_detail_and_reuses_the_create_flow(): void
    {
        $tenant = new Tenant(['id' => 8]);
        $tenant->id = 8;

        $serviceModel = new Service(['id' => 34, 'name' => 'Consulta']);
        $serviceModel->id = 34;

        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getServiceById')
            ->once()
            ->with('srv-1')
            ->andReturn([
                'id' => 'srv-1',
                'accountId' => 'acc-1',
                'category' => 'General',
                'name' => 'Consulta',
                'duration' => 60,
            ]);

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('acc-1')
            ->andReturn($tenant);

        $categoryRepository = Mockery::mock(ServiceCategoryRepositoryInterface::class);
        $category = new ServiceCategory(['id' => 21, 'name' => 'General']);
        $category->id = 21;

        $categoryRepository->shouldReceive('updateOrCreateServiceCategory')
            ->once()
            ->andReturn($category);

        $serviceRepository = Mockery::mock(ServiceRepositoryInterface::class);
        $serviceRepository->shouldReceive('updateOrCreateService')
            ->once()
            ->andReturn($serviceModel);

        $service = $this->makeService(
            client: $client,
            tenantRepository: $tenantRepository,
            upsertServiceCategory: new UpsertServiceCategoryUseCase($categoryRepository),
            upsertService: new UpsertServiceUseCase($serviceRepository),
        );

        $result = $service->handleServiceUpdated(['id' => 'srv-1']);

        $this->assertSame('Servicio registrado o actualizado correctamente.', $result['message']);
        $this->assertSame(34, $result['data']['id']);
    }

    public function test_handle_staff_updated_reloads_the_staff_detail_and_reuses_the_create_flow(): void
    {
        DB::shouldReceive('transaction')->once()->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = new Tenant(['id' => 9]);
        $tenant->id = 9;

        $staff = new Staff(['id' => 44, 'name' => 'Dra. Ruiz']);
        $staff->id = 44;

        $client = Mockery::mock(EspoCrmClientInterface::class);
        $client->shouldReceive('getStaffById')
            ->once()
            ->with('stf-1')
            ->andReturn([
                'id' => 'stf-1',
                'accountId' => 'acc-1',
                'name' => 'Dra. Ruiz',
            ]);

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('acc-1')
            ->andReturn($tenant);

        $staffRepository = Mockery::mock(StaffRepositoryInterface::class);
        $staffRepository->shouldReceive('updateOrCreateStaff')
            ->once()
            ->andReturn($staff);

        $serviceRepository = Mockery::mock(ServiceRepositoryInterface::class);
        $serviceRepository->shouldReceive('allServices')->never();

        $staffServiceRepository = Mockery::mock(StaffServiceRepositoryInterface::class);
        $staffServiceRepository->shouldReceive('syncServices')
            ->once()
            ->with(44, []);

        $location = new TenantLocation(['id' => 7]);
        $location->id = 7;

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('findPrimaryTenantLocationByTenantId')
            ->once()
            ->with(9)
            ->andReturn($location);

        $scheduleRepository = Mockery::mock(ScheduleRepositoryInterface::class);
        $scheduleRepository->shouldReceive('deleteScheduleByStaff')->once()->with(44);

        $upsertStaff = new UpsertStaffUseCase(
            $staffRepository,
            $serviceRepository,
            $staffServiceRepository,
            $locationRepository,
            new ReplaceStaffSchedulesUseCase($scheduleRepository),
        );

        $service = $this->makeService(
            client: $client,
            tenantRepository: $tenantRepository,
            upsertStaff: $upsertStaff,
        );

        $result = $service->handleStaffUpdated(['id' => 'stf-1']);

        $this->assertSame('Staff registrado correctamente.', $result['message']);
        $this->assertSame(44, $result['data']['id']);
    }

    private function makeService(
        ?EspoCrmClientInterface $client = null,
        ?TenantRepositoryInterface $tenantRepository = null,
        ?UpsertTenantFromAccountUseCase $upsertTenant = null,
        ?UpsertServiceCategoryUseCase $upsertServiceCategory = null,
        ?UpsertServiceUseCase $upsertService = null,
        ?UpsertStaffUseCase $upsertStaff = null,
    ): EspoCrmService {
        return new EspoCrmService(
            $client ?? Mockery::mock(EspoCrmClientInterface::class),
            $tenantRepository ?? Mockery::mock(TenantRepositoryInterface::class),
            $upsertTenant ?? new UpsertTenantFromAccountUseCase(
                Mockery::mock(TenantRepositoryInterface::class),
                Mockery::mock(TenantLocationRepositoryInterface::class)
            ),
            $upsertServiceCategory ?? new UpsertServiceCategoryUseCase(Mockery::mock(ServiceCategoryRepositoryInterface::class)),
            $upsertService ?? new UpsertServiceUseCase(Mockery::mock(ServiceRepositoryInterface::class)),
            $upsertStaff ?? new UpsertStaffUseCase(
                Mockery::mock(StaffRepositoryInterface::class),
                Mockery::mock(ServiceRepositoryInterface::class),
                Mockery::mock(StaffServiceRepositoryInterface::class),
                Mockery::mock(TenantLocationRepositoryInterface::class),
                new ReplaceStaffSchedulesUseCase(Mockery::mock(ScheduleRepositoryInterface::class)),
            ),
        );
    }
}
