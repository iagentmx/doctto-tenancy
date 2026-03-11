<?php

namespace Tests\Unit\Observers;

use App\Enums\SchedulableType;
use App\Enums\TenantAdminChannelType;
use App\Enums\TenantAdminRole;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\TenantAdmin;
use App\Models\TenantLocation;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use App\Observers\ResourceObserver;
use App\Observers\ScheduleObserver;
use App\Observers\ServiceObserver;
use App\Observers\StaffObserver;
use App\Observers\TenantAdminObserver;
use App\Observers\TenantLocationObserver;
use Mockery;
use Tests\TestCase;

class OperationalEntityObserversTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_service_observer_excludes_technical_timestamps_from_changed_fields(): void
    {
        $service = new Service();
        $service->forceFill([
            'id' => 10,
            'tenant_id' => 15,
            'name' => 'Consulta',
            'updated_at' => now()->subMinute(),
        ]);
        $service->exists = true;
        $service->syncOriginal();
        $service->name = 'Consulta premium';
        $service->updated_at = now();
        $service->syncChanges();

        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event): bool {
                return $event->event === 'service.updated'
                    && $event->tenantId === 15
                    && $event->entityId === 10
                    && $event->changedFields === ['name'];
            }));

        (new ServiceObserver($eventBus))->updated($service);

        $this->addToAssertionCount(1);
    }

    public function test_tenant_location_observer_publishes_update_events(): void
    {
        $location = new TenantLocation();
        $location->forceFill([
            'id' => 20,
            'tenant_id' => 15,
            'name' => 'Sucursal Norte',
        ]);
        $location->exists = true;
        $location->syncOriginal();
        $location->name = 'Sucursal Centro';
        $location->syncChanges();

        $this->assertObserverPublishes(
            new TenantLocationObserver($this->mockEventBus('tenant_location.updated', 15, 20, ['name'])),
            'updated',
            $location
        );
    }

    public function test_staff_observer_publishes_delete_events(): void
    {
        $staff = new Staff();
        $staff->forceFill([
            'id' => 30,
            'tenant_id' => 15,
            'name' => 'Dr. Soto',
        ]);
        $staff->exists = true;

        $this->assertObserverPublishes(
            new StaffObserver($this->mockEventBus('staff.deleted', 15, 30, [])),
            'deleted',
            $staff
        );
    }

    public function test_resource_observer_publishes_update_events(): void
    {
        $resource = new Resource();
        $resource->forceFill([
            'id' => 40,
            'tenant_id' => 15,
            'name' => 'Consultorio 1',
        ]);
        $resource->exists = true;
        $resource->syncOriginal();
        $resource->name = 'Consultorio 2';
        $resource->syncChanges();

        $this->assertObserverPublishes(
            new ResourceObserver($this->mockEventBus('resource.updated', 15, 40, ['name'])),
            'updated',
            $resource
        );
    }

    public function test_schedule_observer_publishes_delete_events(): void
    {
        $schedule = new Schedule();
        $schedule->forceFill([
            'id' => 50,
            'tenant_id' => 15,
            'schedulable_type' => SchedulableType::Staff,
            'schedulable_id' => 4,
        ]);
        $schedule->exists = true;

        $this->assertObserverPublishes(
            new ScheduleObserver($this->mockEventBus('schedule.deleted', 15, 50, [])),
            'deleted',
            $schedule
        );
    }

    public function test_tenant_admin_observer_publishes_update_events(): void
    {
        $tenantAdmin = new TenantAdmin();
        $tenantAdmin->forceFill([
            'id' => 60,
            'tenant_id' => 15,
            'channel_type' => TenantAdminChannelType::WhatsApp,
            'jid' => '5217711986426@s.whatsapp.net',
            'role' => TenantAdminRole::Owner,
        ]);
        $tenantAdmin->exists = true;
        $tenantAdmin->syncOriginal();
        $tenantAdmin->jid = '5217711986427@s.whatsapp.net';
        $tenantAdmin->syncChanges();

        $this->assertObserverPublishes(
            new TenantAdminObserver($this->mockEventBus('tenant_admin.updated', 15, 60, ['jid'])),
            'updated',
            $tenantAdmin
        );
    }

    private function assertObserverPublishes(object $observer, string $method, object $model): void
    {
        $observer->{$method}($model);

        $this->addToAssertionCount(1);
    }

    private function mockEventBus(string $eventName, int $tenantId, int $entityId, array $changedFields): IntegrationEventBusInterface
    {
        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event) use ($eventName, $tenantId, $entityId, $changedFields): bool {
                return $event->event === $eventName
                    && $event->tenantId === $tenantId
                    && $event->entityId === $entityId
                    && $event->changedFields === $changedFields;
            }));

        return $eventBus;
    }
}
