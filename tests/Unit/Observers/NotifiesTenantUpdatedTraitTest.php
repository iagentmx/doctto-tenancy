<?php

namespace Tests\Unit\Observers;

use App\Models\Service;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Observers\Concerns\NotifiesTenantUpdated;
use Mockery;
use Tests\TestCase;

class NotifiesTenantUpdatedTraitTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_does_not_publish_events_when_tenant_or_entity_ids_are_missing(): void
    {
        $eventBus = Mockery::mock(IntegrationEventBusInterface::class);
        $eventBus->shouldNotReceive('publishEntityChanged');

        $observer = new class($eventBus) {
            use NotifiesTenantUpdated;

            public function __construct(protected IntegrationEventBusInterface $integrationEventBus)
            {
            }

            public function publish(Service $service): void
            {
                $this->publishUpdatedEvent($service, 'service');
            }
        };

        $service = new Service();
        $service->forceFill(['name' => 'Consulta']);
        $service->exists = true;
        $service->syncOriginal();
        $service->name = 'Consulta 2';
        $service->syncChanges();

        $observer->publish($service);

        $this->addToAssertionCount(1);
    }
}
