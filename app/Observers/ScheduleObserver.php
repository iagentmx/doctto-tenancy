<?php

namespace App\Observers;

use App\Models\Schedule;
use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Observers\Concerns\NotifiesTenantUpdated;

class ScheduleObserver
{
    use NotifiesTenantUpdated;

    public function __construct(
        protected IntegrationEventBusInterface $integrationEventBus
    ) {}

    public function updated(Schedule $schedule): void
    {
        $this->publishUpdatedEvent($schedule, 'schedule');
    }

    public function deleted(Schedule $schedule): void
    {
        $this->publishDeletedEvent($schedule, 'schedule');
    }
}
