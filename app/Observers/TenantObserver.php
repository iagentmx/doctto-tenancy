<?php

namespace App\Observers;

use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Tenant;
use App\Observers\Concerns\NotifiesTenantUpdated;

class TenantObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(Tenant $tenant): void
    {
        $this->publishUpdatedEvent($tenant, 'tenant', (int) $tenant->getKey());
    }

    public function deleted(Tenant $tenant): void
    {
        $this->publishDeletedEvent($tenant, 'tenant', (int) $tenant->getKey());
    }
}
