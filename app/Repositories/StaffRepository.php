<?php

namespace App\Repositories;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    public function updateOrCreateStaff(array $where, array $data): Staff
    {
        return Staff::updateOrCreate($where, $data);
    }

    public function findStaffByEmail(string $email): ?Staff
    {
        return Staff::where('email', $email)->first();
    }
}
