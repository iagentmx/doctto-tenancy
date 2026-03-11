<?php

namespace App\Repositories\Contracts;

use App\Models\Schedule;

interface ScheduleRepositoryInterface
{
    public function deleteScheduleByStaff(int $staffId): void;

    public function createSchedule(array $data): Schedule;
}
