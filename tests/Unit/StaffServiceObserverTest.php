<?php

namespace Tests\Unit;

use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffService;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use App\Observers\StaffServiceObserver;
use Mockery;
use Tests\TestCase;

class StaffServiceObserverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_registers_an_updated_event_using_the_staff_tenant_and_the_composite_hash(): void
    {
        $staffService = new StaffService();
        $staffService->forceFill([
            'staff_id' => 12,
            'service_id' => 34,
        ]);
        $staffService->exists = true;
        $staffService->syncOriginal();
        $staffService->service_id = 55;
        $staffService->syncChanges();

        $staff = new Staff();
        $staff->tenant_id = 999;
        $staffService->setRelation('staff', $staff);

        $service = new Service();
        $service->tenant_id = 111;
        $staffService->setRelation('service', $service);

        $expectedEntityId = (int) sprintf('%u', crc32('staff:12|service:55'));

        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event) use ($expectedEntityId): bool {
                return $event->event === 'staff_service.updated'
                    && $event->tenantId === 999
                    && $event->entityId === $expectedEntityId
                    && $event->changedFields === ['service_id'];
            }));

        $observer = new StaffServiceObserver($eventBus);
        $observer->updated($staffService);

        $this->addToAssertionCount(1);
    }

    public function test_it_registers_a_deleted_event_using_the_service_tenant_when_staff_is_missing(): void
    {
        $staffService = new StaffService();
        $staffService->forceFill([
            'staff_id' => 20,
            'service_id' => 44,
        ]);
        $staffService->exists = true;
        $staffService->setRelation('staff', null);

        $service = new Service();
        $service->tenant_id = 321;
        $staffService->setRelation('service', $service);

        $expectedEntityId = (int) sprintf('%u', crc32('staff:20|service:44'));

        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event) use ($expectedEntityId): bool {
                return $event->event === 'staff_service.deleted'
                    && $event->tenantId === 321
                    && $event->entityId === $expectedEntityId
                    && $event->changedFields === [];
            }));

        $observer = new StaffServiceObserver($eventBus);
        $observer->deleted($staffService);

        $this->addToAssertionCount(1);
    }
}
