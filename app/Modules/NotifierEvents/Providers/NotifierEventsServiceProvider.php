<?php

namespace App\Modules\NotifierEvents\Providers;

use App\Modules\NotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\NotifierEvents\Infrastructure\Http\HttpN8nPublisher;
use App\Modules\NotifierEvents\Services\DestinationPublisherRegistry;
use App\Modules\NotifierEvents\Services\IntegrationEventBus;
use Illuminate\Support\ServiceProvider;

class NotifierEventsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(IntegrationEventBusInterface::class, IntegrationEventBus::class);
        $this->app->bind(HttpN8nPublisher::class, HttpN8nPublisher::class);
        $this->app->tag([HttpN8nPublisher::class], 'notifier-event.publishers');

        $this->app->singleton(DestinationPublisherRegistry::class, function ($app): DestinationPublisherRegistry {
            return new DestinationPublisherRegistry($app->tagged('notifier-event.publishers'));
        });
    }
}
