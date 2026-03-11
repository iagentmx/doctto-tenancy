<?php

namespace App\Repositories;

use App\Enums\IntegrationEventDeliveryStatus;
use App\Models\IntegrationEventDelivery;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class IntegrationEventDeliveryRepository implements IntegrationEventDeliveryRepositoryInterface
{
    public function createIntegrationEventDelivery(array $data): IntegrationEventDelivery
    {
        return IntegrationEventDelivery::query()->create($data);
    }

    public function findIntegrationEventDeliveryById(int $deliveryId): ?IntegrationEventDelivery
    {
        return IntegrationEventDelivery::query()
            ->with('outbox')
            ->find($deliveryId);
    }

    public function claimPendingIntegrationEventDelivery(int $deliveryId): ?IntegrationEventDelivery
    {
        return DB::transaction(function () use ($deliveryId): ?IntegrationEventDelivery {
            /** @var IntegrationEventDelivery|null $delivery */
            $delivery = IntegrationEventDelivery::query()
                ->with('outbox')
                ->lockForUpdate()
                ->find($deliveryId);

            if (! $delivery || $delivery->status !== IntegrationEventDeliveryStatus::Pending) {
                return null;
            }

            if ($delivery->next_retry_at !== null && $delivery->next_retry_at->isFuture()) {
                return null;
            }

            $delivery->status = IntegrationEventDeliveryStatus::Processing;
            $delivery->last_attempt_at = now()->utc();
            $delivery->save();

            return $delivery;
        });
    }

    public function markIntegrationEventDeliveryAsDelivered(int $deliveryId, array $data): void
    {
        $delivery = IntegrationEventDelivery::query()->find($deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->fill($data + [
            'status' => IntegrationEventDeliveryStatus::Delivered,
            'delivered_at' => now()->utc(),
            'next_retry_at' => null,
            'last_error' => null,
        ]);
        $delivery->save();
    }

    public function markIntegrationEventDeliveryForRetry(int $deliveryId, array $data): void
    {
        $delivery = IntegrationEventDelivery::query()->find($deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->fill($data + [
            'status' => IntegrationEventDeliveryStatus::Pending,
        ]);
        $delivery->save();
    }

    public function markIntegrationEventDeliveryAsFailed(int $deliveryId, array $data): void
    {
        $delivery = IntegrationEventDelivery::query()->find($deliveryId);

        if (! $delivery) {
            return;
        }

        $delivery->fill($data + [
            'status' => IntegrationEventDeliveryStatus::Failed,
            'next_retry_at' => null,
        ]);
        $delivery->save();
    }

    public function listPendingIntegrationEventDeliveriesReadyForDispatch(?string $destination, int $limit): Collection
    {
        return IntegrationEventDelivery::query()
            ->where('status', IntegrationEventDeliveryStatus::Pending->value)
            ->when($destination !== null, fn ($query) => $query->where('destination', $destination))
            ->where(function ($query): void {
                $query->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now()->utc());
            })
            ->orderBy('id')
            ->limit($limit)
            ->get();
    }

    public function requeueFailedIntegrationEventDeliveries(?string $destination, int $limit): int
    {
        $deliveries = IntegrationEventDelivery::query()
            ->where('status', IntegrationEventDeliveryStatus::Failed->value)
            ->when($destination !== null, fn ($query) => $query->where('destination', $destination))
            ->orderBy('id')
            ->limit($limit)
            ->get();

        foreach ($deliveries as $delivery) {
            $delivery->status = IntegrationEventDeliveryStatus::Pending;
            $delivery->next_retry_at = null;
            $delivery->save();
        }

        return $deliveries->count();
    }
}
