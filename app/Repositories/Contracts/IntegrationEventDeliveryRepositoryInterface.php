<?php

namespace App\Repositories\Contracts;

use App\Models\IntegrationEventDelivery;
use Illuminate\Support\Collection;

interface IntegrationEventDeliveryRepositoryInterface
{
    public function createIntegrationEventDelivery(array $data): IntegrationEventDelivery;

    public function findIntegrationEventDeliveryById(int $deliveryId): ?IntegrationEventDelivery;

    public function claimPendingIntegrationEventDelivery(int $deliveryId): ?IntegrationEventDelivery;

    public function markIntegrationEventDeliveryAsDelivered(int $deliveryId, array $data): void;

    public function markIntegrationEventDeliveryForRetry(int $deliveryId, array $data): void;

    public function markIntegrationEventDeliveryAsFailed(int $deliveryId, array $data): void;

    public function listPendingIntegrationEventDeliveriesReadyForDispatch(?string $destination, int $limit): Collection;

    public function requeueFailedIntegrationEventDeliveries(?string $destination, int $limit): int;
}
