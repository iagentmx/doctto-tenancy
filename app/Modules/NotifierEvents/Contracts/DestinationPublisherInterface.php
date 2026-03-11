<?php

namespace App\Modules\NotifierEvents\Contracts;

use App\Models\IntegrationEventOutbox;
use App\Modules\NotifierEvents\DTO\PublishResult;

interface DestinationPublisherInterface
{
    public function destination(): string;

    public function publish(IntegrationEventOutbox $event): PublishResult;
}
