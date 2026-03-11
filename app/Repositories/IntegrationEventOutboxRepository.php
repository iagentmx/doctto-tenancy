<?php

namespace App\Repositories;

use App\Models\IntegrationEventOutbox;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;

class IntegrationEventOutboxRepository implements IntegrationEventOutboxRepositoryInterface
{
    public function createIntegrationEventOutbox(array $data): IntegrationEventOutbox
    {
        return IntegrationEventOutbox::query()->create($data);
    }

    public function findIntegrationEventOutboxById(int $outboxId): ?IntegrationEventOutbox
    {
        return IntegrationEventOutbox::query()
            ->with('deliveries')
            ->find($outboxId);
    }

    public function markIntegrationEventOutboxAsDispatched(int $outboxId): void
    {
        $outbox = IntegrationEventOutbox::query()->find($outboxId);

        if (! $outbox || $outbox->dispatched_at !== null) {
            return;
        }

        $outbox->dispatched_at = now()->utc();
        $outbox->save();
    }
}
