<?php

namespace Tests\Unit\Modules\TenantEntities;

use App\Enums\IndustryType;
use App\Enums\OperationType;
use App\Enums\SchedulableType;
use App\Enums\StaffRole;
use App\Enums\TenantAdminChannelType;
use App\Enums\TenantAdminRole;
use App\Exceptions\ApiServiceException;
use App\Models\Resource;
use App\Models\ResourceType;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantAdmin;
use App\Models\TenantLocation;
use App\Modules\TenantEntities\Services\TenantEntitiesService;
use App\Modules\TenantEntities\UseCases\TenantAdmins\RegisterTenantAdmin;
use App\Repositories\Contracts\TenantAdminRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Mockery;
use Tests\TestCase;

class TenantEntitiesServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_get_by_jid_returns_the_expected_projection(): void
    {
        $tenant = new Tenant([
            'id' => 15,
            'espocrm_id' => 'acc-1',
            'jid' => '5217711986426@s.whatsapp.net',
            'name' => 'Tenant Demo',
            'industry_type' => IndustryType::Healthcare,
            'is_active' => true,
            'description' => 'Clinica demo',
            'settings' => ['assistantName' => 'Sofia'],
        ]);
        $tenant->id = 15;

        $location = new TenantLocation([
            'address' => 'Av. Juarez 1',
            'time_zone' => 'America/Mexico_City',
            'url_map' => 'https://maps.test/1',
        ]);

        $service = new Service([
            'name' => 'Consulta',
            'description' => 'General',
            'duration_minutes' => 30,
            'price' => 500,
        ]);

        $schedule = new Schedule([
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '12:00:00',
        ]);

        $staff = new Staff([
            'name' => 'Dra. Perez',
            'phone' => '7710000000',
            'email' => 'dra@test.mx',
            'settings' => ['about' => 'Perfil', 'specialty' => 'Odontologia'],
        ]);
        $staff->setRelation('schedules', new EloquentCollection([$schedule]));
        $staff->setRelation('services', new EloquentCollection([$service]));

        $tenant->setRelation('primaryLocation', $location);
        $tenant->setRelation('staff', new EloquentCollection([$staff]));

        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('findTenantByJid')
            ->once()
            ->with('5217711986426@s.whatsapp.net')
            ->andReturn($tenant);

        $service = $this->makeService($repository);

        $result = $service->getByJid('5217711986426@s.whatsapp.net');

        $this->assertSame('Tenant Demo', $result['name']);
        $this->assertSame('Av. Juarez 1', $result['address']);
        $this->assertSame('lunes', $result['staff'][0]['schedules'][0]['day']);
        $this->assertSame('Consulta', $result['staff'][0]['services'][0]['name']);
    }

    public function test_get_by_jid_throws_not_found_when_the_tenant_does_not_exist(): void
    {
        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('findTenantByJid')
            ->once()
            ->with('missing')
            ->andReturn(null);

        $service = $this->makeService($repository);

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('Tenant no encontrado');

        $service->getByJid('missing');
    }

    public function test_get_catalog_by_tenant_id_maps_all_operational_entities_and_orders_schedules(): void
    {
        $tenant = new Tenant([
            'id' => 20,
            'espocrm_id' => 'acc-20',
            'jid' => '5217711986427@s.whatsapp.net',
            'name' => 'Catalogo',
            'industry_type' => IndustryType::Healthcare,
            'operation_type' => OperationType::MultiStaff,
            'is_active' => true,
            'description' => 'Catalogo demo',
            'settings' => [
                'assistantName' => 'Laura',
                'urlReviewPlatform' => 'https://reviews.test',
                'features' => [
                    'surveysEnabled' => true,
                    'billingEnabled' => false,
                ],
            ],
        ]);
        $tenant->id = 20;

        $location = new TenantLocation([
            'id' => 1,
            'tenant_id' => 20,
            'name' => 'Matriz',
            'address' => 'Centro',
            'time_zone' => 'America/Mexico_City',
            'url_map' => 'https://maps.test',
            'is_primary' => true,
            'is_active' => true,
            'settings' => ['foo' => 'bar'],
        ]);
        $location->id = 1;

        $category = new ServiceCategory([
            'id' => 7,
            'tenant_id' => 20,
            'name' => 'General',
        ]);
        $category->id = 7;

        $serviceModel = new Service([
            'id' => 3,
            'tenant_id' => 20,
            'espocrm_id' => 'srv-1',
            'category_id' => 7,
            'name' => 'Consulta',
            'description' => 'General',
            'duration_minutes' => 30,
            'price' => 300,
            'is_active' => true,
            'settings' => ['channel' => 'web'],
        ]);
        $serviceModel->id = 3;

        $staff = new Staff([
            'id' => 4,
            'tenant_id' => 20,
            'espocrm_id' => 'stf-1',
            'name' => 'Dra. Perez',
            'role' => StaffRole::Doctor,
            'phone' => '7710000000',
            'email' => 'dra@test.mx',
            'is_active' => true,
            'settings' => ['about' => 'Perfil', 'specialty' => 'Ortodoncia'],
        ]);
        $staff->id = 4;
        $staff->setRelation('services', new EloquentCollection([$serviceModel]));

        $resourceType = new ResourceType([
            'id' => 2,
            'name' => 'Consultorio',
            'is_active' => true,
        ]);
        $resourceType->id = 2;

        $resource = new Resource([
            'id' => 5,
            'tenant_id' => 20,
            'tenant_location_id' => 1,
            'resource_type_id' => 2,
            'name' => 'Box 1',
            'description' => 'Principal',
            'is_active' => true,
            'settings' => ['capacity' => 1],
        ]);
        $resource->id = 5;
        $resource->setRelation('resourceType', $resourceType);

        $staffScheduleLate = new Schedule([
            'id' => 9,
            'tenant_id' => 20,
            'schedulable_type' => SchedulableType::Staff,
            'schedulable_id' => 4,
            'tenant_location_id' => 1,
            'day_of_week' => 3,
            'start_time' => '16:00:00',
            'end_time' => '18:00:00',
            'is_active' => true,
        ]);
        $staffScheduleLate->id = 9;

        $staffScheduleEarly = new Schedule([
            'id' => 8,
            'tenant_id' => 20,
            'schedulable_type' => SchedulableType::Staff,
            'schedulable_id' => 4,
            'tenant_location_id' => 1,
            'day_of_week' => 1,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'is_active' => true,
        ]);
        $staffScheduleEarly->id = 8;

        $resourceSchedule = new Schedule([
            'id' => 10,
            'tenant_id' => 20,
            'schedulable_type' => SchedulableType::Resource,
            'schedulable_id' => 5,
            'tenant_location_id' => 1,
            'day_of_week' => 2,
            'start_time' => '12:00:00',
            'end_time' => '13:00:00',
            'is_active' => true,
        ]);
        $resourceSchedule->id = 10;

        $staff->setRelation('schedules', new EloquentCollection([$staffScheduleLate, $staffScheduleEarly]));
        $resource->setRelation('schedules', new EloquentCollection([$resourceSchedule]));

        $tenantAdmin = new TenantAdmin([
            'id' => 11,
            'tenant_id' => 20,
            'channel_type' => TenantAdminChannelType::WhatsApp,
            'jid' => '5217711986427@s.whatsapp.net',
            'role' => TenantAdminRole::Owner,
            'is_active' => true,
            'settings' => ['lang' => 'es'],
        ]);
        $tenantAdmin->id = 11;

        $tenant->setRelation('tenantLocations', new EloquentCollection([$location]));
        $tenant->setRelation('staff', new EloquentCollection([$staff]));
        $tenant->setRelation('services', new EloquentCollection([$serviceModel]));
        $tenant->setRelation('serviceCategories', new EloquentCollection([$category]));
        $tenant->setRelation('tenantAdmins', new EloquentCollection([$tenantAdmin]));
        $tenant->setRelation('resources', new EloquentCollection([$resource]));

        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('findTenantById')
            ->once()
            ->with(20)
            ->andReturn($tenant);

        $service = $this->makeService($repository);

        $result = $service->getCatalogByTenantId(20);

        $this->assertSame('Laura', $result['tenant']['settings']['assistant_name']);
        $this->assertSame([3], $result['staff'][0]['service_ids']);
        $this->assertSame('Consultorio', $result['resources'][0]['resource_type']['name']);
        $this->assertCount(3, $result['schedules']);
        $this->assertTrue(collect($result['schedules'])->contains(
            fn (array $schedule): bool => $schedule['schedulable_type'] === SchedulableType::Resource->value
                && $schedule['schedulable_id'] === 5
        ));
        $this->assertTrue(collect($result['schedules'])->contains(
            fn (array $schedule): bool => $schedule['schedulable_type'] === SchedulableType::Staff->value
                && $schedule['day_name'] === 'lunes'
        ));
    }

    public function test_get_by_espocrm_id_returns_the_wrapped_payload_and_throws_on_missing_tenant(): void
    {
        $tenant = new Tenant([
            'id' => 5,
            'espocrm_id' => 'acc-5',
            'jid' => '5217711986428@s.whatsapp.net',
            'name' => 'Tenant Espo',
        ]);
        $tenant->id = 5;

        $repository = Mockery::mock(TenantRepositoryInterface::class);
        $repository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('acc-5')
            ->andReturn($tenant);
        $repository->shouldReceive('findTenantByEspoCrmId')
            ->once()
            ->with('missing')
            ->andReturn(null);

        $service = $this->makeService($repository);

        $result = $service->getByEspoCrmId('acc-5');
        $this->assertSame('success', $result['status']);
        $this->assertSame('Tenant Espo', $result['result']['name']);

        try {
            $service->getByEspoCrmId('missing');
            $this->fail('Se esperaba una excepcion.');
        } catch (ApiServiceException $exception) {
            $this->assertSame(404, $exception->getStatusCode());
        }
    }

    private function makeService(TenantRepositoryInterface $repository): TenantEntitiesService
    {
        return new TenantEntitiesService(
            $repository,
            new RegisterTenantAdmin(Mockery::mock(TenantAdminRepositoryInterface::class)),
        );
    }
}
