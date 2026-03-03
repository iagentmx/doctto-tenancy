<?php

namespace App\Repositories;

use App\Models\StaffSchedule;
use App\Repositories\Contracts\StaffScheduleRepositoryInterface;

class StaffScheduleRepository implements StaffScheduleRepositoryInterface
{
    public function deleteStaffScheduleByStaff(int $staffId): void
    {
        StaffSchedule::where('staff_id', $staffId)->delete();
    }

    public function createStaffSchedule(array $data): StaffSchedule
    {
        return StaffSchedule::create($data);
    }
}
