<?php

namespace App\Repositories\Contracts;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;

interface StaffRepositoryInterface
{
    public function listStaffByTenantId(int $tenantId): Collection;
    public function findStaffByTenantAndId(int $tenantId, int $staffId): ?Staff;
    public function createStaff(array $data): Staff;
    public function updateStaff(int $staffId, array $data): Staff;
    public function deleteStaffById(int $staffId): void;
    public function updateOrCreateStaff(array $where, array $data): Staff;
    public function findStaffByEmail(string $email): ?Staff;
}
