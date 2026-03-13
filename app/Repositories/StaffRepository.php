<?php

namespace App\Repositories;

use App\Models\Staff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class StaffRepository implements StaffRepositoryInterface
{
    public function listStaffByTenantId(int $tenantId): Collection
    {
        return Staff::query()
            ->with('services')
            ->where('tenant_id', $tenantId)
            ->orderBy('id')
            ->get();
    }

    public function findStaffByTenantAndId(int $tenantId, int $staffId): ?Staff
    {
        return Staff::query()
            ->with('services')
            ->where('tenant_id', $tenantId)
            ->whereKey($staffId)
            ->first();
    }

    public function createStaff(array $data): Staff
    {
        return Staff::query()
            ->create($data)
            ->load('services');
    }

    public function updateStaff(int $staffId, array $data): Staff
    {
        $staff = Staff::query()->findOrFail($staffId);
        $staff->fill($data);
        $staff->save();

        return $staff->load('services');
    }

    public function deleteStaffById(int $staffId): void
    {
        $staff = Staff::query()->findOrFail($staffId);
        $staff->delete();
    }

    public function updateOrCreateStaff(array $where, array $data): Staff
    {
        return Staff::updateOrCreate($where, $data);
    }

    public function findStaffByEmail(string $email): ?Staff
    {
        return Staff::where('email', $email)->first();
    }
}
