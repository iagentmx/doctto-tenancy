<?php

namespace App\Modules\N8nNotifierEvents\Providers;

use App\Modules\N8nNotifierEvents\Contracts\IntegrationEventBusInterface;
use App\Modules\N8nNotifierEvents\Contracts\N8nClientInterface;
use App\Modules\N8nNotifierEvents\Infrastructure\Http\HttpN8nClient;
use App\Modules\N8nNotifierEvents\Services\N8nEventBus;
use Illuminate\Support\ServiceProvider;

class N8nServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(N8nClientInterface::class, HttpN8nClient::class);
        $this->app->bind(IntegrationEventBusInterface::class, N8nEventBus::class);
    }
}
