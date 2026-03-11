<?php

namespace App\Providers;

use App\Modules\EspoCrmTenantIngestion\Providers\EspoCrmTenantIngestionServiceProvider;
use App\Modules\NotifierEvents\Providers\NotifierEventsServiceProvider;
use App\Modules\TenantEntities\Providers\TenantEntitiesServiceProvider;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\StaffService;
use App\Models\Tenant;
use App\Models\TenantAdmin;
use App\Models\TenantLocation;
use App\Observers\ResourceObserver;
use App\Observers\ScheduleObserver;
use App\Observers\ServiceObserver;
use App\Observers\StaffObserver;
use App\Observers\StaffServiceObserver;
use App\Observers\TenantObserver;
use App\Observers\TenantAdminObserver;
use App\Observers\TenantLocationObserver;
use App\Repositories\ResourceRepository;
use App\Repositories\ResourceTypeRepository;
use App\Repositories\ScheduleRepository;
use App\Repositories\ServiceCategoryRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\IntegrationEventDeliveryRepository;
use App\Repositories\IntegrationEventOutboxRepository;
use App\Repositories\StaffRepository;
use App\Repositories\StaffServiceRepository;
use App\Repositories\TenantAdminRepository;
use App\Repositories\TenantRepository;
use App\Repositories\TenantLocationRepository;
use App\Repositories\Contracts\ServiceCategoryRepositoryInterface;
use App\Repositories\Contracts\ResourceRepositoryInterface;
use App\Repositories\Contracts\ResourceTypeRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use App\Repositories\Contracts\TenantAdminRepositoryInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use Illuminate\Database\Eloquent\Relations\Relation;
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
        $this->app->register(NotifierEventsServiceProvider::class);
        $this->app->register(TenantEntitiesServiceProvider::class);

        // Repositories
        $this->app->bind(TenantRepositoryInterface::class, TenantRepository::class);
        $this->app->bind(TenantLocationRepositoryInterface::class, TenantLocationRepository::class);
        $this->app->bind(ResourceRepositoryInterface::class, ResourceRepository::class);
        $this->app->bind(ResourceTypeRepositoryInterface::class, ResourceTypeRepository::class);
        $this->app->bind(ServiceRepositoryInterface::class, ServiceRepository::class);
        $this->app->bind(ServiceCategoryRepositoryInterface::class, ServiceCategoryRepository::class);
        $this->app->bind(StaffRepositoryInterface::class, StaffRepository::class);
        $this->app->bind(ScheduleRepositoryInterface::class, ScheduleRepository::class);
        $this->app->bind(StaffServiceRepositoryInterface::class, StaffServiceRepository::class);
        $this->app->bind(TenantAdminRepositoryInterface::class, TenantAdminRepository::class);
        $this->app->bind(IntegrationEventOutboxRepositoryInterface::class, IntegrationEventOutboxRepository::class);
        $this->app->bind(IntegrationEventDeliveryRepositoryInterface::class, IntegrationEventDeliveryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Relation::enforceMorphMap([
            'staff' => Staff::class,
            'resource' => Resource::class,
        ]);

        Tenant::observe(TenantObserver::class);
        Service::observe(ServiceObserver::class);
        Staff::observe(StaffObserver::class);
        TenantLocation::observe(TenantLocationObserver::class);
        Resource::observe(ResourceObserver::class);
        Schedule::observe(ScheduleObserver::class);
        TenantAdmin::observe(TenantAdminObserver::class);
        StaffService::observe(StaffServiceObserver::class);
    }
}
