<?php

namespace App\Observers;

use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Service;
use App\Observers\Concerns\NotifiesTenantUpdated;

class ServiceObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(Service $service): void
    {
        $this->publishUpdatedEvent($service, 'service');
    }

    public function deleted(Service $service): void
    {
        $this->publishDeletedEvent($service, 'service');
    }
}
