<?php

namespace App\Modules\TenantEntities\UseCases\Staff;

use App\Exceptions\ApiServiceException;
use App\Repositories\Contracts\StaffRepositoryInterface;

final class DeleteStaff
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(int $tenantId, int $staffId): void
    {
        $staff = $this->staffRepository->findStaffByTenantAndId($tenantId, $staffId);

        if (! $staff) {
            throw new ApiServiceException('Staff no encontrado', 404);
        }

        $this->staffRepository->deleteStaffById($staff->id);
    }
}
