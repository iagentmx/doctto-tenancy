<?php

namespace App\Observers;

use App\Models\StaffService;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Observers\Concerns\NotifiesTenantUpdated;

class StaffServiceObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(StaffService $staffService): void
    {
        $this->publishUpdatedStaffServiceEvent($staffService);
    }

    public function deleted(StaffService $staffService): void
    {
        $this->publishDeletedStaffServiceEvent($staffService);
    }

    private function publishUpdatedStaffServiceEvent(StaffService $staffService): void
    {
        $staffService->loadMissing('staff', 'service');

        $tenantId = $staffService->staff?->tenant_id ?? $staffService->service?->tenant_id;
        $entityId = $this->resolveStaffServiceEntityId($staffService);

        $this->publishUpdatedEvent($staffService, 'staff_service', $tenantId, $entityId);
    }

    private function publishDeletedStaffServiceEvent(StaffService $staffService): void
    {
        $staffService->loadMissing('staff', 'service');

        $tenantId = $staffService->staff?->tenant_id ?? $staffService->service?->tenant_id;
        $entityId = $this->resolveStaffServiceEntityId($staffService);

        $this->publishDeletedEvent($staffService, 'staff_service', $tenantId, $entityId);
    }

    private function resolveStaffServiceEntityId(StaffService $staffService): int
    {
        return (int) sprintf(
            '%u',
            crc32(sprintf(
                'staff:%s|service:%s',
                (string) $staffService->staff_id,
                (string) $staffService->service_id
            ))
        );
    }
}
