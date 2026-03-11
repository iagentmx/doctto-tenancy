<?php

namespace App\Modules\NotifierEvents\Contracts;

use App\Modules\NotifierEvents\DTO\IntegrationEvent;

interface IntegrationEventBusInterface
{
    public function publishEntityChanged(IntegrationEvent $event): void;
}
