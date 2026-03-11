<?php

namespace App\Repositories;

use App\Enums\SchedulableType;
use App\Models\Schedule;
use App\Repositories\Contracts\ScheduleRepositoryInterface;

class ScheduleRepository implements ScheduleRepositoryInterface
{
    public function deleteScheduleByStaff(int $staffId): void
    {
        Schedule::query()
            ->where('schedulable_type', SchedulableType::Staff->value)
            ->where('schedulable_id', $staffId)
            ->get()
            ->each
            ->delete();
    }

    public function createSchedule(array $data): Schedule
    {
        return Schedule::query()->create($data);
    }
}
