<?php

namespace App\Observers;

use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Models\Staff;
use App\Observers\Concerns\NotifiesTenantUpdated;

class StaffObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(Staff $staff): void
    {
        $this->publishUpdatedEvent($staff, 'staff');
    }

    public function deleted(Staff $staff): void
    {
        $this->publishDeletedEvent($staff, 'staff');
    }
}
