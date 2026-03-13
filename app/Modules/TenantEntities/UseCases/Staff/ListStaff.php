<?php

namespace App\Modules\TenantEntities\UseCases\Staff;

use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

final class ListStaff
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(int $tenantId): Collection
    {
        return $this->staffRepository->listStaffByTenantId($tenantId);
    }
}
