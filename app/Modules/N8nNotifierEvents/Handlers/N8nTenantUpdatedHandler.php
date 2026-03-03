<?php

namespace App\Modules\N8nNotifierEvents\Handlers;

use App\Modules\N8nNotifierEvents\Contracts\N8nClientInterface;
use App\Modules\N8nNotifierEvents\DTO\TenantUpdated;
use Illuminate\Support\Facades\Log;

class N8nTenantUpdatedHandler
{
    public function __construct(
        protected N8nClientInterface $n8nClient
    ) {}

    public function handle(TenantUpdated $event): void
    {
        try {
            $this->n8nClient->postUpdateTenantWebhook([
                'tenant_jid' => $event->tenantJid,
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ No se pudo notificar a n8n (update tenant)', [
                'tenant_jid' => $event->tenantJid,
                'error'      => $e->getMessage(),
            ]);
        }
    }
}
