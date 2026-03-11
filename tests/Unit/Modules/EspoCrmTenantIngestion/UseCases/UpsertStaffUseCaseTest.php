<?php

namespace Tests\Unit\Modules\EspoCrmTenantIngestion\UseCases;

use App\Exceptions\EspoCrmWebhookException;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantLocation;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\ReplaceStaffSchedulesUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertStaffUseCase;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class UpsertStaffUseCaseTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        DB::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_execute_requires_a_primary_location_for_the_tenant(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = new Tenant(['id' => 10]);
        $tenant->id = 10;

        $staff = new Staff(['id' => 55]);
        $staff->id = 55;

        $staffRepository = Mockery::mock(StaffRepositoryInterface::class);
        $staffRepository->shouldReceive('updateOrCreateStaff')
            ->once()
            ->andReturn($staff);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('findPrimaryTenantLocationByTenantId')
            ->once()
            ->with(10)
            ->andReturn(null);

        $useCase = new UpsertStaffUseCase(
            $staffRepository,
            Mockery::mock(ServiceRepositoryInterface::class),
            Mockery::mock(StaffServiceRepositoryInterface::class),
            $locationRepository,
            new ReplaceStaffSchedulesUseCase(Mockery::mock(ScheduleRepositoryInterface::class)),
        );

        $this->expectException(EspoCrmWebhookException::class);
        $this->expectExceptionMessage('No se encontró ubicación principal para el tenant.');

        $useCase->execute($tenant, [
            'id' => 'staff-1',
            'name' => 'Dra. Gomez',
        ]);
    }

    public function test_execute_replaces_schedules_and_syncs_only_services_owned_by_the_tenant(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $tenant = new Tenant(['id' => 10]);
        $tenant->id = 10;

        $staff = new Staff(['id' => 55]);
        $staff->id = 55;

        $primaryLocation = new TenantLocation(['id' => 7]);
        $primaryLocation->id = 7;

        $tenantService = new Service(['id' => 1, 'tenant_id' => 10, 'espocrm_id' => 'srv-a']);
        $tenantService->id = 1;

        $otherTenantService = new Service(['id' => 2, 'tenant_id' => 99, 'espocrm_id' => 'srv-b']);
        $otherTenantService->id = 2;

        $staffRepository = Mockery::mock(StaffRepositoryInterface::class);
        $staffRepository->shouldReceive('updateOrCreateStaff')
            ->once()
            ->with(
                ['tenant_id' => 10, 'espocrm_id' => 'staff-1'],
                Mockery::on(fn (array $data): bool => $data['name'] === 'Dra. Gomez')
            )
            ->andReturn($staff);

        $locationRepository = Mockery::mock(TenantLocationRepositoryInterface::class);
        $locationRepository->shouldReceive('findPrimaryTenantLocationByTenantId')
            ->once()
            ->with(10)
            ->andReturn($primaryLocation);

        $scheduleRepository = Mockery::mock(ScheduleRepositoryInterface::class);
        $scheduleRepository->shouldReceive('deleteScheduleByStaff')
            ->once()
            ->with(55);
        $scheduleRepository->shouldReceive('createSchedule')->never();

        $replaceSchedules = new ReplaceStaffSchedulesUseCase($scheduleRepository);

        $serviceRepository = Mockery::mock(ServiceRepositoryInterface::class);
        $serviceRepository->shouldReceive('allServices')
            ->once()
            ->andReturn(new EloquentCollection([$tenantService, $otherTenantService]));

        $staffServiceRepository = Mockery::mock(StaffServiceRepositoryInterface::class);
        $staffServiceRepository->shouldReceive('syncServices')
            ->once()
            ->with(55, [1]);

        $useCase = new UpsertStaffUseCase(
            $staffRepository,
            $serviceRepository,
            $staffServiceRepository,
            $locationRepository,
            $replaceSchedules,
        );

        $result = $useCase->execute($tenant, [
            'id' => 'staff-1',
            'name' => 'Dra. Gomez',
            'servicesIds' => ['srv-a', 'srv-b', 'srv-missing'],
        ]);

        $this->assertSame($staff, $result);
    }
}
