<?php

namespace App\Modules\NotifierEvents\Services;

use App\Enums\IntegrationEventDeliveryStatus;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;
use Illuminate\Support\Str;

class IntegrationEventBus implements IntegrationEventBusInterface
{
    public function __construct(
        protected IntegrationEventOutboxRepositoryInterface $integrationEventOutboxRepository,
        protected IntegrationEventDeliveryRepositoryInterface $integrationEventDeliveryRepository,
        protected IntegrationEventDeliveryDispatcher $integrationEventDeliveryDispatcher,
    ) {}

    public function publishEntityChanged(IntegrationEvent $event): void
    {
        if ($event->tenantId <= 0 || $event->entityId <= 0 || trim($event->event) === '') {
            return;
        }

        $outbox = $this->integrationEventOutboxRepository->createIntegrationEventOutbox([
            'event_uuid' => (string) Str::uuid(),
            'event_name' => trim($event->event),
            'tenant_id' => $event->tenantId,
            'entity_type' => $event->entityType(),
            'entity_id' => $event->entityId,
            'payload' => $event->toArray(),
            'occurred_at' => $event->occurredAt,
            'correlation_id' => $this->resolveCorrelationId(),
            'source' => $this->resolveSource(),
        ]);

        foreach ($this->enabledDestinations() as $destination) {
            $delivery = $this->integrationEventDeliveryRepository->createIntegrationEventDelivery([
                'integration_event_outbox_id' => $outbox->id,
                'destination' => $destination,
                'status' => IntegrationEventDeliveryStatus::Pending,
                'attempts' => 0,
            ]);

            $this->integrationEventDeliveryDispatcher->dispatchDelivery((int) $delivery->id, (int) $outbox->id);
        }
    }

    /**
     * @return list<string>
     */
    private function enabledDestinations(): array
    {
        $destinations = config('notifier_events.destinations', []);

        $enabled = [];

        foreach ($destinations as $name => $destinationConfig) {
            if (($destinationConfig['enabled'] ?? false) === true) {
                $enabled[] = (string) $name;
            }
        }

        return $enabled;
    }

    private function resolveCorrelationId(): ?string
    {
        $correlationId = request()->headers->get('X-Correlation-Id');

        return is_string($correlationId) && trim($correlationId) !== ''
            ? trim($correlationId)
            : null;
    }

    private function resolveSource(): ?string
    {
        if (app()->runningInConsole()) {
            return 'console';
        }

        $path = request()->path();

        return is_string($path) && trim($path) !== ''
            ? trim($path)
            : null;
    }
}
