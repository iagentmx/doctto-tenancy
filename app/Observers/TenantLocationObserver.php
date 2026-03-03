<?php

namespace App\Observers;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\TenantLocation;

class TenantLocationObserver
{
    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function saved(TenantLocation $tenantLocation): void
    {
        $tenantLocation->loadMissing('tenant');

        $jid = $tenantLocation->tenant?->jid ?? null;
        if (!is_string($jid) || trim($jid) === '') {
            return;
        }

        $this->integrationEventBus->publishTenantUpdated($tenantLocation->tenant);
    }
}
