<?php

namespace Tests\Unit;

use App\Models\Tenant;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use App\Observers\TenantObserver;
use Mockery;
use Tests\TestCase;

class TenantObserverTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_registers_an_integration_event_when_a_tenant_is_updated(): void
    {
        $tenant = new Tenant();
        $tenant->forceFill([
            'id' => 123,
            'jid' => '5217711986426@s.whatsapp.net',
            'name' => 'Tenant Demo',
        ]);
        $tenant->exists = true;
        $tenant->syncOriginal();
        $tenant->name = 'Tenant Demo Editado';
        $tenant->syncChanges();

        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event) {
                return $event->event === 'tenant.updated'
                    && $event->tenantId === 123
                    && $event->entityId === 123
                    && $event->changedFields === ['name'];
            }));

        $observer = new TenantObserver($eventBus);

        $observer->updated($tenant);

        $this->addToAssertionCount(1);
    }

    public function test_it_registers_an_integration_event_when_a_tenant_is_deleted(): void
    {
        $tenant = new Tenant();
        $tenant->forceFill([
            'id' => 123,
            'jid' => '5217711986426@s.whatsapp.net',
            'name' => 'Tenant Demo',
        ]);
        $tenant->exists = true;

        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldReceive('publishEntityChanged')
            ->once()
            ->with(Mockery::on(function (IntegrationEvent $event) {
                return $event->event === 'tenant.deleted'
                    && $event->tenantId === 123
                    && $event->entityId === 123
                    && $event->changedFields === [];
            }));

        $observer = new TenantObserver($eventBus);

        $observer->deleted($tenant);

        $this->addToAssertionCount(1);
    }
}
