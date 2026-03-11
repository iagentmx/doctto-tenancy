<?php

namespace App\Observers;

use App\Models\Resource;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Observers\Concerns\NotifiesTenantUpdated;

class ResourceObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(Resource $resource): void
    {
        $this->publishUpdatedEvent($resource, 'resource');
    }

    public function deleted(Resource $resource): void
    {
        $this->publishDeletedEvent($resource, 'resource');
    }
}
