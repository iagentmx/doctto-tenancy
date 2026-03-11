<?php

namespace Tests\Unit;

use App\Enums\IntegrationEventDeliveryStatus;
use App\Models\IntegrationEventDelivery;
use App\Models\IntegrationEventOutbox;
use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use App\Modules\NotifierEvents\Services\IntegrationEventBus;
use App\Modules\NotifierEvents\Services\IntegrationEventDeliveryDispatcher;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;
use Mockery;
use Tests\TestCase;

class IntegrationEventBusTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_creates_outbox_and_one_delivery_per_enabled_destination(): void
    {
        config()->set('notifier_events.destinations', [
            'n8n' => ['enabled' => true],
            'audit' => ['enabled' => true],
            'disabled' => ['enabled' => false],
        ]);

        request()->headers->set('X-Correlation-Id', 'corr-123');

        $outboxRepository = Mockery::mock(IntegrationEventOutboxRepositoryInterface::class);
        $deliveryRepository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $dispatcher = Mockery::mock(IntegrationEventDeliveryDispatcher::class);

        $outbox = new IntegrationEventOutbox();
        $outbox->id = 77;

        $outboxRepository->shouldReceive('createIntegrationEventOutbox')
            ->once()
            ->with(Mockery::on(function (array $data): bool {
                return $data['event_name'] === 'service.updated'
                    && $data['tenant_id'] === 10
                    && $data['entity_type'] === 'service'
                    && $data['entity_id'] === 99
                    && $data['payload']['metadata']['changed_fields'] === ['name']
                    && $data['correlation_id'] === 'corr-123'
                    && $data['source'] === 'console'
                    && is_string($data['event_uuid'])
                    && $data['event_uuid'] !== '';
            }))
            ->andReturn($outbox);

        $deliveryN8n = new IntegrationEventDelivery();
        $deliveryN8n->id = 101;

        $deliveryAudit = new IntegrationEventDelivery();
        $deliveryAudit->id = 102;

        $deliveryRepository->shouldReceive('createIntegrationEventDelivery')
            ->once()
            ->with([
                'integration_event_outbox_id' => 77,
                'destination' => 'n8n',
                'status' => IntegrationEventDeliveryStatus::Pending,
                'attempts' => 0,
            ])
            ->andReturn($deliveryN8n);

        $deliveryRepository->shouldReceive('createIntegrationEventDelivery')
            ->once()
            ->with([
                'integration_event_outbox_id' => 77,
                'destination' => 'audit',
                'status' => IntegrationEventDeliveryStatus::Pending,
                'attempts' => 0,
            ])
            ->andReturn($deliveryAudit);

        $dispatcher->shouldReceive('dispatchDelivery')->once()->with(101, 77);
        $dispatcher->shouldReceive('dispatchDelivery')->once()->with(102, 77);

        $service = new IntegrationEventBus($outboxRepository, $deliveryRepository, $dispatcher);

        $service->publishEntityChanged(new IntegrationEvent(
            event: 'service.updated',
            tenantId: 10,
            entityId: 99,
            occurredAt: '2026-03-11T12:00:00Z',
            changedFields: ['name']
        ));

        $this->addToAssertionCount(1);
    }

    public function test_it_ignores_invalid_events_before_touching_repositories(): void
    {
        $outboxRepository = Mockery::mock(IntegrationEventOutboxRepositoryInterface::class);
        $deliveryRepository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $dispatcher = Mockery::mock(IntegrationEventDeliveryDispatcher::class);

        $outboxRepository->shouldNotReceive('createIntegrationEventOutbox');
        $deliveryRepository->shouldNotReceive('createIntegrationEventDelivery');
        $dispatcher->shouldNotReceive('dispatchDelivery');

        $service = new IntegrationEventBus($outboxRepository, $deliveryRepository, $dispatcher);

        $service->publishEntityChanged(new IntegrationEvent(
            event: '   ',
            tenantId: 0,
            entityId: -5,
            occurredAt: '2026-03-11T12:00:00Z'
        ));

        $this->addToAssertionCount(1);
    }
}
