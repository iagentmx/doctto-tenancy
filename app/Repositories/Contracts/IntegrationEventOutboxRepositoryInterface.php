<?php

namespace App\Repositories\Contracts;

use App\Models\IntegrationEventOutbox;

interface IntegrationEventOutboxRepositoryInterface
{
    public function createIntegrationEventOutbox(array $data): IntegrationEventOutbox;

    public function findIntegrationEventOutboxById(int $outboxId): ?IntegrationEventOutbox;

    public function markIntegrationEventOutboxAsDispatched(int $outboxId): void;
}
