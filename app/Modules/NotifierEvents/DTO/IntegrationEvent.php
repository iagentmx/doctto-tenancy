<?php

namespace App\Modules\NotifierEvents\DTO;

class IntegrationEvent
{
    /**
     * @param list<string> $changedFields
     */
    public function __construct(
        public string $event,
        public int $tenantId,
        public int $entityId,
        public string $occurredAt,
        public array $changedFields = []
    ) {}

    public function entityType(): string
    {
        return (string) str($this->event)->before('.');
    }

    public function toArray(): array
    {
        return [
            'event' => $this->event,
            'tenant_id' => $this->tenantId,
            'entity_id' => $this->entityId,
            'occurred_at' => $this->occurredAt,
            'metadata' => [
                'changed_fields' => array_values($this->changedFields),
            ],
        ];
    }
}
