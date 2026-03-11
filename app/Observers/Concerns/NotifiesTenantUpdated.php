<?php

namespace App\Observers\Concerns;

use App\Modules\NotifierEvents\DTO\IntegrationEvent;
use Illuminate\Database\Eloquent\Model;

trait NotifiesTenantUpdated
{
    protected function publishUpdatedEvent(
        Model $model,
        string $entityName,
        ?int $tenantId = null,
        ?int $entityId = null
    ): void
    {
        $this->publishEntityChangedEvent(
            eventName: "{$entityName}.updated",
            tenantId: $tenantId ?? $this->resolveTenantId($model),
            entityId: $entityId ?? $this->resolveEntityId($model),
            changedFields: $this->resolveChangedFields($model)
        );
    }

    protected function publishDeletedEvent(
        Model $model,
        string $entityName,
        ?int $tenantId = null,
        ?int $entityId = null
    ): void
    {
        $this->publishEntityChangedEvent(
            eventName: "{$entityName}.deleted",
            tenantId: $tenantId ?? $this->resolveTenantId($model),
            entityId: $entityId ?? $this->resolveEntityId($model),
            changedFields: []
        );
    }

    protected function publishEntityChangedEvent(
        string $eventName,
        ?int $tenantId,
        ?int $entityId,
        array $changedFields
    ): void
    {
        if (! $tenantId || ! $entityId) {
            return;
        }

        $this->integrationEventBus->publishEntityChanged(new IntegrationEvent(
            event: $eventName,
            tenantId: $tenantId,
            entityId: $entityId,
            occurredAt: now()->utc()->toIso8601String(),
            changedFields: $changedFields
        ));
    }

    protected function resolveTenantId(Model $model): ?int
    {
        $tenantId = $model->getAttribute('tenant_id');

        return is_numeric($tenantId) ? (int) $tenantId : null;
    }

    protected function resolveEntityId(Model $model): ?int
    {
        $key = $model->getKey();

        return is_numeric($key) ? (int) $key : null;
    }

    /**
     * @return list<string>
     */
    protected function resolveChangedFields(Model $model): array
    {
        $ignoredFields = ['created_at', 'updated_at', 'deleted_at'];

        $changedFields = array_keys($model->getChanges());
        $changedFields = array_values(array_diff($changedFields, $ignoredFields));

        return array_map('strval', $changedFields);
    }
}
