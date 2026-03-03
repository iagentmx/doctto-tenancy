<?php

namespace App\Providers;

use App\Modules\EspoCrmTenantIngestion\Providers\EspoCrmTenantIngestionServiceProvider;
use App\Modules\N8nNotifierEvents\Providers\N8nServiceProvider;
use App\Modules\TenantEntities\Providers\TenantEntitiesServiceProvider;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantLocation;
use App\Observers\ServiceObserver;
use App\Observers\StaffObserver;
use App\Observers\TenantObserver;
use App\Observers\TenantLocationObserver;
use App\Repositories\ServiceCategoryRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\StaffRepository;
use App\Repositories\StaffScheduleRepository;
use App\Repositories\StaffServiceRepository;
use App\Repositories\TenantRepository;
use App\Repositories\TenantLocationRepository;
use App\Repositories\Contracts\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StaffScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Modules
        $this->app->register(EspoCrmTenantIngestionServiceProvider::class);
        $this->app->register(N8nServiceProvider::class);
        $this->app->register(TenantEntitiesServiceProvider::class);

        // Repositories
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantLocationRepositoryInterface::class, TenantLocationRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(ServiceCategoryRepositoryInterface::class, ServiceCategoryRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
        $this->app->bind(StaffScheduleRepositoryInterface::class, StaffScheduleRepository::class);
        $this->app->bind(StaffServiceRepositoryInterface::class, StaffServiceRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Tenant::observe(TenantObserver::class);
        Service::observe(ServiceObserver::class);
        Staff::observe(StaffObserver::class);
        TenantLocation::observe(TenantLocationObserver::class);
    }
}
