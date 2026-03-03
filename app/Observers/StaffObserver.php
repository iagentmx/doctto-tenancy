<?php

namespace App\Observers;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Staff;

class StaffObserver
{
    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function saved(Staff $staff): void
    {
        $staff->loadMissing('tenant');

        $jid = $staff->tenant?->jid ?? null;
        if (!is_string($jid) || trim($jid) === '') {
            return;
        }

        $this->integrationEventBus->publishTenantUpdated($staff->tenant);
    }
}
