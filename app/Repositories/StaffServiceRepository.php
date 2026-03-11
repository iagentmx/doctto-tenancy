<?php

namespace App\Repositories;

use App\Repositories\Contracts\StaffServiceRepositoryInterface;
use Illuminate\Support\Facades\DB;

class StaffServiceRepository implements StaffServiceRepositoryInterface
{
    public function syncServices(int $staffId, array $serviceIds): void
    {
        $normalizedServiceIds = array_values(array_unique(array_map(
            static fn ($serviceId): int => (int) $serviceId,
            array_filter($serviceIds, static fn ($serviceId): bool => is_numeric($serviceId))
        )));

        DB::table('staff_services')
            ->where('staff_id', $staffId)
            ->delete();

        if ($normalizedServiceIds === []) {
            return;
        }

        DB::table('staff_services')->insert(array_map(
            static fn (int $serviceId): array => [
                'staff_id' => $staffId,
                'service_id' => $serviceId,
            ],
            $normalizedServiceIds
        ));
    }
}
