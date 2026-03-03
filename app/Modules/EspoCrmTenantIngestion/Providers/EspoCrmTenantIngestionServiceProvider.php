<?php

namespace App\Modules\EspoCrmTenantIngestion\Providers;

use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmClientInterface;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmConfigProviderInterface;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmServiceInterface;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Config\EnvEspoCrmConfigProvider;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Http\HttpEspoCrmClient;
use App\Modules\EspoCrmTenantIngestion\Services\EspoCrmService;
use Illuminate\Support\ServiceProvider;

class EspoCrmTenantIngestionServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(EspoCrmConfigProviderInterface::class, EnvEspoCrmConfigProvider::class);

        $this->app->bind(EspoCrmClientInterface::class, HttpEspoCrmClient::class);

        $this->app->bind(EspoCrmServiceInterface::class, EspoCrmService::class);
    }
}
