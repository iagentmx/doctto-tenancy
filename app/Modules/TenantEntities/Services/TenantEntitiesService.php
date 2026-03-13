<?php

namespace App\Modules\TenantEntities\Services;

use App\Exceptions\ApiServiceException;
use App\Models\Resource;
use App\Models\Schedule;
use App\Models\Service;
use App\Models\Staff;
use App\Models\Tenant;
use App\Models\TenantAdmin;
use App\Models\TenantLocation;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use App\Modules\TenantEntities\DTO\StaffData;
use App\Modules\TenantEntities\DTO\TenantAdminData;
use App\Modules\TenantEntities\UseCases\Staff\CreateStaff;
use App\Modules\TenantEntities\UseCases\Staff\DeleteStaff;
use App\Modules\TenantEntities\UseCases\Staff\GetStaff;
use App\Modules\TenantEntities\UseCases\Staff\ListStaff;
use App\Modules\TenantEntities\UseCases\Staff\UpdateStaff;
use App\Modules\TenantEntities\UseCases\TenantAdmins\RegisterTenantAdmin;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantEntitiesService implements TenantEntitiesServiceInterface
{
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
        protected RegisterTenantAdmin $registerTenantAdmin,
        protected ListStaff $listStaff,
        protected GetStaff $getStaff,
        protected CreateStaff $createStaffUseCase,
        protected UpdateStaff $updateStaffUseCase,
        protected DeleteStaff $deleteStaffUseCase,
    ) {}

    /**
     * Map day number (1-7) to day name.
     */
    private function mapDayName(int $day): string
    {
        return [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábado',
            7 => 'domingo',
        ][$day] ?? 'unknown';
    }

    private function mapTenantSettings(?array $settings): array
    {
        return [
            'assistant_name' => $settings['assistantName'] ?? null,
            'url_review_platform' => $settings['urlReviewPlatform'] ?? null,
            'features' => [
                'surveys_enabled' => $settings['features']['surveysEnabled'] ?? null,
                'billing_enabled' => $settings['features']['billingEnabled'] ?? null,
            ],
        ];
    }

    private function mapTenant(Tenant $tenant): array
    {
        return [
            'id' => $tenant->id,
            'espocrm_id' => $tenant->espocrm_id,
            'jid' => $tenant->jid,
            'name' => $tenant->name,
            'industry_type' => $tenant->industry_type?->value ?? null,
            'operation_type' => $tenant->operation_type?->value ?? null,
            'is_active' => $tenant->is_active,
            'description' => $tenant->description,
            'settings' => $this->mapTenantSettings($tenant->settings),
        ];
    }

    private function mapLocation(TenantLocation $location): array
    {
        return [
            'id' => $location->id,
            'tenant_id' => $location->tenant_id,
            'name' => $location->name,
            'address' => $location->address,
            'time_zone' => $location->time_zone,
            'url_map' => $location->url_map,
            'is_primary' => $location->is_primary,
            'is_active' => $location->is_active,
            'settings' => $location->settings ?? [],
        ];
    }

    private function mapService(Service $service): array
    {
        return [
            'id' => $service->id,
            'tenant_id' => $service->tenant_id,
            'espocrm_id' => $service->espocrm_id,
            'category_id' => $service->category_id,
            'name' => $service->name,
            'description' => $service->description,
            'duration_minutes' => $service->duration_minutes,
            'price' => $service->price,
            'is_active' => $service->is_active,
            'settings' => $service->settings ?? [],
        ];
    }

    private function mapStaff(Staff $staff): array
    {
        return [
            'id' => $staff->id,
            'tenant_id' => $staff->tenant_id,
            'espocrm_id' => $staff->espocrm_id,
            'name' => $staff->name,
            'role' => $staff->role?->value ?? null,
            'phone' => $staff->phone,
            'email' => $staff->email,
            'is_active' => $staff->is_active,
            'settings' => [
                'about' => $staff->settings['about'] ?? null,
                'specialty' => $staff->settings['specialty'] ?? null,
            ],
            'service_ids' => $staff->services->pluck('id')->values()->all(),
        ];
    }

    private function findTenantOrFailByJid(string $jid): Tenant
    {
        $tenant = $this->tenantRepository->findTenantByJid($jid);

        if (! $tenant) {
            throw new ApiServiceException('Tenant no encontrado', 404);
        }

        return $tenant;
    }

    private function mapResource(Resource $resource): array
    {
        return [
            'id' => $resource->id,
            'tenant_id' => $resource->tenant_id,
            'tenant_location_id' => $resource->tenant_location_id,
            'resource_type_id' => $resource->resource_type_id,
            'name' => $resource->name,
            'description' => $resource->description,
            'is_active' => $resource->is_active,
            'settings' => $resource->settings ?? [],
            'resource_type' => $resource->resourceType ? [
                'id' => $resource->resourceType->id,
                'name' => $resource->resourceType->name,
            ] : null,
        ];
    }

    private function mapTenantAdmin(TenantAdmin $tenantAdmin): array
    {
        return [
            'id' => $tenantAdmin->id,
            'tenant_id' => $tenantAdmin->tenant_id,
            'channel_type' => $tenantAdmin->channel_type?->value ?? null,
            'jid' => $tenantAdmin->jid,
            'role' => $tenantAdmin->role?->value ?? null,
            'is_active' => $tenantAdmin->is_active,
            'settings' => $tenantAdmin->settings ?? [],
        ];
    }

    private function mapSchedule(Schedule $schedule): array
    {
        return [
            'id' => $schedule->id,
            'tenant_id' => $schedule->tenant_id,
            'schedulable_type' => $schedule->schedulable_type?->value ?? null,
            'schedulable_id' => $schedule->schedulable_id,
            'tenant_location_id' => $schedule->tenant_location_id,
            'day_of_week' => $schedule->day_of_week,
            'day_name' => $this->mapDayName($schedule->day_of_week),
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'is_active' => $schedule->is_active,
        ];
    }

    /**
     * Retrieve a tenant by its WhatsApp JID.
     */
    public function getByJid(string $jid): array
    {
        $tenant = $this->tenantRepository->findTenantByJid($jid);

        if (! $tenant) {
            throw new ApiServiceException('Tenant no encontrado', 404);
        }

        return [
            'id'            => $tenant->id,
            'espocrm_id'    => $tenant->espocrm_id,
            'jid'           => $tenant->jid,
            'name'          => $tenant->name,
            'industry_type' => $tenant->industry_type?->value ?? null,
            'is_active'     => $tenant->is_active,
            'address'       => $tenant->primaryLocation?->address,
            'description'   => $tenant->description,
            'time_zone'     => $tenant->primaryLocation?->time_zone,
            'url_map'       => $tenant->primaryLocation?->url_map,
            'settings'      => $tenant->settings,

            // STAFF
            'staff' => $tenant->staff->map(function ($staff) {
                return [
                    'name'  => $staff->name,
                    'phone' => $staff->phone,
                    'email' => $staff->email,

                    // SETTINGS (solo specialty y about)
                    'settings' => [
                        'about'     => $staff->settings['about'] ?? null,
                        'specialty' => $staff->settings['specialty'] ?? null,
                    ],

                    // SCHEDULES
                    'schedules' => $staff->schedules->map(function ($sch) {
                        return [
                            'day'        => $this->mapDayName($sch->day_of_week),
                            'start_time' => $sch->start_time,
                            'end_time'   => $sch->end_time,
                        ];
                    })->values(),

                    // STAFF SERVICES
                    'services' => $staff->services->map(function ($service) {
                        return [
                            'name'             => $service->name,
                            'description'      => $service->description,
                            'duration_minutes' => $service->duration_minutes,
                            'price'            => $service->price,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    public function getCatalogByTenantId(int $tenantId): array
    {
        $tenant = $this->tenantRepository->findTenantById($tenantId);

        if (! $tenant) {
            throw new ApiServiceException('Tenant no encontrado', 404);
        }

        $resourceSchedules = $tenant->resources
            ->flatMap(fn(Resource $resource) => $resource->schedules);

        $staffSchedules = $tenant->staff
            ->flatMap(fn(Staff $staff) => $staff->schedules);

        return [
            'tenant' => $this->mapTenant($tenant),
            'locations' => $tenant->tenantLocations
                ->map(fn(TenantLocation $location) => $this->mapLocation($location))
                ->values()
                ->all(),
            'staff' => $tenant->staff
                ->map(fn(Staff $staff) => $this->mapStaff($staff))
                ->values()
                ->all(),
            'services' => $tenant->services
                ->map(fn(Service $service) => $this->mapService($service))
                ->values()
                ->all(),
            'service_categories' => $tenant->serviceCategories
                ->map(fn($category) => [
                    'id' => $category->id,
                    'tenant_id' => $category->tenant_id,
                    'name' => $category->name,
                ])
                ->values()
                ->all(),
            'tenant_admins' => $tenant->tenantAdmins
                ->map(fn(TenantAdmin $tenantAdmin) => $this->mapTenantAdmin($tenantAdmin))
                ->values()
                ->all(),
            'resources' => $tenant->resources
                ->map(fn(Resource $resource) => $this->mapResource($resource))
                ->values()
                ->all(),
            'schedules' => $staffSchedules
                ->concat($resourceSchedules)
                ->sortBy([
                    ['schedulable_type', 'asc'],
                    ['schedulable_id', 'asc'],
                    ['day_of_week', 'asc'],
                    ['start_time', 'asc'],
                ])
                ->map(fn(Schedule $schedule) => $this->mapSchedule($schedule))
                ->values()
                ->all(),
        ];
    }

    public function registerTenantAdmin(TenantAdminData $tenantAdminData): array
    {
        return $this->mapTenantAdmin(
            $this->registerTenantAdmin->execute($tenantAdminData)
        );
    }

    public function listStaffByTenantJid(string $tenantJid): array
    {
        $tenant = $this->findTenantOrFailByJid($tenantJid);

        return $this->listStaff
            ->execute($tenant->id)
            ->map(fn(Staff $staff) => $this->mapStaff($staff))
            ->values()
            ->all();
    }

    public function getStaffByTenantJidAndId(string $tenantJid, int $staffId): array
    {
        $tenant = $this->findTenantOrFailByJid($tenantJid);

        return $this->mapStaff(
            $this->getStaff->execute($tenant->id, $staffId)
        );
    }

    public function createStaff(string $tenantJid, StaffData $staffData): array
    {
        $tenant = $this->findTenantOrFailByJid($tenantJid);

        return $this->mapStaff(
            $this->createStaffUseCase->execute($staffData->forTenant($tenant->id))
        );
    }

    public function updateStaff(string $tenantJid, int $staffId, StaffData $staffData): array
    {
        $tenant = $this->findTenantOrFailByJid($tenantJid);

        return $this->mapStaff(
            $this->updateStaffUseCase->execute($tenant->id, $staffId, $staffData->forTenant($tenant->id))
        );
    }

    public function deleteStaff(string $tenantJid, int $staffId): void
    {
        $tenant = $this->findTenantOrFailByJid($tenantJid);

        $this->deleteStaffUseCase->execute($tenant->id, $staffId);
    }

    /**
     * Retrieve a tenant by its EspoCRM ID.
     */
    public function getByEspoCrmId(string $espocrmId): array
    {
        $tenant = $this->tenantRepository->findTenantByEspoCrmId($espocrmId);

        if (! $tenant) {
            throw new ApiServiceException('Tenant not found for given EspoCRM ID.', 404);
        }

        return [
            'status' => 'success',
            'message' => 'Tenant found successfully.',
            'result' => $tenant->toArray(),
        ];
    }
}
