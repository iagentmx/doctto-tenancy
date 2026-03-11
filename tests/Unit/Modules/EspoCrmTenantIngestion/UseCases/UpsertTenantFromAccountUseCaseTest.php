<?php

namespace Tests\Unit\Modules\EspoCrmTenantIngestion\UseCases;

use App\Models\Tenant;
use App\Models\TenantLocation;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertTenantFromAccountUseCase;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpsertTenantFromAccountUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        DB::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_execute_upserts_tenant_and_primary_location_in_a_single_transaction(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->id = 15;
        $tenant->shouldReceive('load')
            ->once()
            ->with('primaryLocation')
            ->andReturnSelf();

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('updateOrCreateTenant')
            ->once()
            ->with(
                ['espocrm_id' => 'acc-1'],
                Mockery::on(fn (array $data): bool => $data['name'] === 'Tenant Uno')
            )
            ->andReturn($tenant);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('updateOrCreatePrimaryTenantLocation')
            ->once()
            ->with(15, Mockery::on(fn (array $data): bool => $data['name'] === 'Matriz'))
            ->andReturn(new TenantLocation());

        $useCase = new UpsertTenantFromAccountUseCase($tenantRepository, $locationRepository);

        $result = $useCase->execute([
            'id' => 'acc-1',
            'name' => 'Tenant Uno',
        ]);

        $this->assertSame($tenant, $result);
    }

    public function test_execute_update_existing_updates_tenant_and_primary_location(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = Mockery::mock(Tenant::class)->makePartial();
        $tenant->id = 25;
        $tenant->shouldReceive('load')
            ->once()
            ->with('primaryLocation')
            ->andReturnSelf();

        $tenantRepository = Mockery::mock(TenantRepositoryInterface::class);
        $tenantRepository->shouldReceive('updateTenant')
            ->once()
            ->with(25, Mockery::on(fn (array $data): bool => $data['espocrm_id'] === 'acc-25'))
            ->andReturn($tenant);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('updateOrCreatePrimaryTenantLocation')
            ->once()
            ->with(25, Mockery::on(fn (array $data): bool => $data['is_primary'] === true))
            ->andReturn(new TenantLocation());

        $useCase = new UpsertTenantFromAccountUseCase($tenantRepository, $locationRepository);

        $result = $useCase->executeUpdateExisting(25, [
            'id' => 'acc-25',
            'name' => 'Tenant Existente',
            'billingAddressCity' => 'Pachuca',
        ]);

        $this->assertSame($tenant, $result);
    }
}
