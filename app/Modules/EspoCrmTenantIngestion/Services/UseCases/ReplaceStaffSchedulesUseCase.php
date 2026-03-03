<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\StaffSchedulesMapper;
use App\Repositories\Contracts\StaffScheduleRepositoryInterface;

final class ReplaceStaffSchedulesUseCase
{
    public function __construct(
        protected StaffScheduleRepositoryInterface $staffScheduleRepository
    ) {}

    public function execute(int $staffId, int $tenantLocationId, array $payload): void
    {
        // Igual que antes: borrar todo y recrear
        $this->staffScheduleRepository->deleteStaffScheduleByStaff($staffId);

        $rows = StaffSchedulesMapper::map($staffId, $tenantLocationId, $payload);

        foreach ($rows as $row) {
            $this->staffScheduleRepository->createStaffSchedule($row);
        }
    }
}
