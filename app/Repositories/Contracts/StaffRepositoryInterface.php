<?php

namespace App\Repositories\Contracts;

use App\Models\Staff;

interface StaffRepositoryInterface
{
    public function updateOrCreateStaff(array $where, array $data): Staff;
    public function findStaffByEmail(string $email): ?Staff;
}
