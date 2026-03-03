<?php

namespace App\Repositories\Contracts;

use App\Models\StaffSchedule;

interface StaffScheduleRepositoryInterface
{
    public function deleteStaffScheduleByStaff(int $staffId): void;

    public function createStaffSchedule(array $data): StaffSchedule;
}
