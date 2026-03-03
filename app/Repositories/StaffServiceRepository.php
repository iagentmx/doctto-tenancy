<?php

namespace App\Repositories;

use App\Models\StaffService;
use App\Repositories\Contracts\StaffServiceRepositoryInterface;

class StaffServiceRepository implements StaffServiceRepositoryInterface
{
    public function syncServices(int $staffId, array $serviceIds): void
    {
        StaffService::where('staff_id', $staffId)->delete();

        foreach ($serviceIds as $serviceId) {
            StaffService::create([
                'staff_id'   => $staffId,
                'service_id' => $serviceId,
            ]);
        }
    }
}
