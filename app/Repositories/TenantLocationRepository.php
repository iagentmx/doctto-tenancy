<?php

namespace App\Repositories;

use App\Models\TenantLocation;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;

class TenantLocationRepository implements TenantLocationRepositoryInterface
{
    public function updateOrCreatePrimaryTenantLocation(int $tenantId, array $data): TenantLocation
    {
        $name = $data['name'] ?? 'Matriz';

        TenantLocation::query()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->where('name', '!=', $name)
            ->get()
            ->each(function (TenantLocation $tenantLocation): void {
                $tenantLocation->is_primary = false;
                $tenantLocation->save();
            });

        return TenantLocation::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => $name,
            ],
            [
                'tenant_id' => $tenantId,
                'name' => $name,
                'address' => $data['address'] ?? null,
                'time_zone' => $data['time_zone'] ?? null,
                'url_map' => $data['url_map'] ?? null,
                'is_primary' => true,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'settings' => is_array($data['settings'] ?? null) ? $data['settings'] : [],
            ]
        );
    }

    public function findPrimaryTenantLocationByTenantId(int $tenantId): ?TenantLocation
    {
        return TenantLocation::query()
            ->where('tenant_id', $tenantId)
            ->where('is_primary', true)
            ->first();
    }
}
