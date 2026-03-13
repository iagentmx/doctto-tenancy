<?php

namespace App\Modules\TenantEntities\UseCases\Staff;

use App\Exceptions\ApiServiceException;
use App\Models\Staff;
use App\Modules\TenantEntities\DTO\StaffData;
use App\Repositories\Contracts\StaffRepositoryInterface;

final class UpdateStaff
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(int $tenantId, int $staffId, StaffData $staffData): Staff
    {
        $staff = $this->staffRepository->findStaffByTenantAndId($tenantId, $staffId);

        if (! $staff) {
            throw new ApiServiceException('Staff no encontrado', 404);
        }

        return $this->staffRepository->updateStaff($staff->id, $staffData->toRepositoryData());
    }
}
