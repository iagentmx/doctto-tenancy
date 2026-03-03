<?php

namespace App\Modules\TenantEntities\Providers;

use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use App\Modules\TenantEntities\Services\TenantEntitiesService;
use Illuminate\Support\ServiceProvider;

class TenantEntitiesServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(TenantEntitiesServiceInterface::class, TenantEntitiesService::class);
    }
}
