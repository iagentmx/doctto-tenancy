<?php

namespace App\Observers;

use App\Models\TenantAdmin;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Observers\Concerns\NotifiesTenantUpdated;

class TenantAdminObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(TenantAdmin $tenantAdmin): void
    {
        $this->publishUpdatedEvent($tenantAdmin, 'tenant_admin');
    }

    public function deleted(TenantAdmin $tenantAdmin): void
    {
        $this->publishDeletedEvent($tenantAdmin, 'tenant_admin');
    }
}
