<?php

namespace App\Observers;

use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\TenantLocation;
use App\Observers\Concerns\NotifiesTenantUpdated;

class TenantLocationObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(TenantLocation $tenantLocation): void
    {
        $this->publishUpdatedEvent($tenantLocation, 'tenant_location');
    }

    public function deleted(TenantLocation $tenantLocation): void
    {
        $this->publishDeletedEvent($tenantLocation, 'tenant_location');
    }
}
