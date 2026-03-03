<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Exceptions\EspoCrmWebhookException;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\StaffToStaffMapper;
use App\Models\Staff;
use App\Models\Tenant;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;

final class UpsertStaffUseCase
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
        protected ServiceRepositoryInterface $serviceRepository,
        protected StaffServiceRepositoryInterface $staffServiceRepository,
        protected TenantLocationRepositoryInterface $tenantLocationRepository,
        protected ReplaceStaffSchedulesUseCase $replaceStaffSchedules,
    ) {}

    public function execute(Tenant $tenant, array $payload): Staff
    {
        $mapped = StaffToStaffMapper::map($tenant->id, $payload);

        $staff = $this->staffRepository->updateOrCreateStaff(
            $mapped['criteria'],
            $mapped['data']
        );

        $primaryLocation = $this->tenantLocationRepository->findPrimaryTenantLocationByTenantId($tenant->id);

        if (! $primaryLocation) {
            throw new EspoCrmWebhookException('No se encontró ubicación principal para el tenant.', 422);
        }

        // Horarios (igual que antes: delete + create)
        $this->replaceStaffSchedules->execute($staff->id, $primaryLocation->id, $payload);

        // STAFF SERVICES (igual que antes)
        $serviceIds = [];

        if (!empty($payload['servicesIds']) && is_array($payload['servicesIds'])) {
            $services = $this->serviceRepository->allServices()
                ->where('tenant_id', $tenant->id);

            foreach ($payload['servicesIds'] as $espocrmServiceId) {

                $service = $services->firstWhere('espocrm_id', $espocrmServiceId);

                if ($service) {
                    $serviceIds[] = $service->id;
                }
            }
        }

        $this->staffServiceRepository->syncServices($staff->id, $serviceIds);

        return $staff;
    }
}
