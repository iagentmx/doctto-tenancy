<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\StaffSchedulesMapper;
use App\Repositories\Contracts\ScheduleRepositoryInterface;

final class ReplaceStaffSchedulesUseCase
{
    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {}

    public function execute(int $tenantId, int $staffId, int $tenantLocationId, array $payload): void
    {
        // Igual que antes: borrar todo y recrear
        $this->scheduleRepository->deleteScheduleByStaff($staffId);

        $rows = StaffSchedulesMapper::map($tenantId, $staffId, $tenantLocationId, $payload);

        foreach ($rows as $row) {
            $this->scheduleRepository->createSchedule($row);
        }
    }
}
