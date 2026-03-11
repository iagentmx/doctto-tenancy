<?php

namespace App\Modules\NotifierEvents\Services;

use App\Modules\NotifierEvents\Jobs\DeliverIntegrationEventDeliveryJob;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;

class IntegrationEventDeliveryDispatcher
{
    public function __construct(
        protected IntegrationEventDeliveryRepositoryInterface $integrationEventDeliveryRepository,
        protected IntegrationEventOutboxRepositoryInterface $integrationEventOutboxRepository,
    ) {}

    public function dispatchPendingDeliveries(?string $destination = null, ?int $limit = null): int
    {
        $limit ??= (int) config('notifier_events.dispatch.limit', 100);

        $deliveries = $this->integrationEventDeliveryRepository
            ->listPendingIntegrationEventDeliveriesReadyForDispatch($destination, $limit);

        foreach ($deliveries as $delivery) {
            $this->dispatchDelivery((int) $delivery->id, (int) $delivery->integration_event_outbox_id);
        }

        return $deliveries->count();
    }

    public function requeueFailedDeliveries(?string $destination = null, ?int $limit = null): int
    {
        $limit ??= (int) config('notifier_events.dispatch.limit', 100);

        return $this->integrationEventDeliveryRepository
            ->requeueFailedIntegrationEventDeliveries($destination, $limit);
    }

    public function dispatchDelivery(int $deliveryId, int $outboxId): void
    {
        DeliverIntegrationEventDeliveryJob::dispatch($deliveryId)->afterCommit();
        $this->integrationEventOutboxRepository->markIntegrationEventOutboxAsDispatched($outboxId);
    }
}
