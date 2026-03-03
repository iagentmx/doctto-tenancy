<?php

namespace App\Observers;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Tenant;

class TenantObserver
{
    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function saved(Tenant $tenant): void
    {
        $jid = $tenant->jid ?? null;
        if (!is_string($jid) || trim($jid) === '') {
            return;
        }

        $this->integrationEventBus->publishTenantUpdated($tenant);
    }
}
