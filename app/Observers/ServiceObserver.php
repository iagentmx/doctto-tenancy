<?php

namespace App\Observers;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Service;

class ServiceObserver
{
    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function saved(Service $service): void
    {
        $service->loadMissing('tenant');

        $jid = $service->tenant?->jid ?? null;
        if (!is_string($jid) || trim($jid) === '') {
            return;
        }

        $this->integrationEventBus->publishTenantUpdated($service->tenant);
    }
}
