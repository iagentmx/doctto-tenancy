<?php

namespace App\Modules\TenantEntities\UseCases\Staff;

use App\Models\Staff;
use App\Modules\TenantEntities\DTO\StaffData;
use App\Repositories\Contracts\StaffRepositoryInterface;

final class CreateStaff
{
    public function __construct(
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    public function execute(StaffData $staffData): Staff
    {
        return $this->staffRepository->createStaff($staffData->toRepositoryData());
    }
}
