<?php

namespace App\Modules\N8nNotifierEvents\Services;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\N8nNotifierEvents\DTO\TenantUpdated;
use App\Modules\N8nNotifierEvents\Handlers\N8nTenantUpdatedHandler;
use App\Modules\N8nNotifierEvents\Support\EspoCrmWebhookRouteDetector;
use App\Modules\N8nNotifierEvents\Support\N8nWebhookOnceGuard;
use App\Models\Tenant;

class N8nEventBus implements IntegrationEventBusInterface
{
    public function __construct(
        protected N8nTenantUpdatedHandler $tenantUpdatedHandler
    ) {}

    public function publishTenantUpdated(Tenant $tenant): void
    {
        if (! EspoCrmWebhookRouteDetector::shouldNotify()) {
            return;
        }

        $jid = $tenant->jid ?? null;
        if (!is_string($jid) || trim($jid) === '') {
            return;
        }

        $jid = trim($jid);

        if (! N8nWebhookOnceGuard::shouldSend("tenant:{$jid}")) {
            return;
        }

        $this->tenantUpdatedHandler->handle(new TenantUpdated($jid));
    }
}
