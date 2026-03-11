<?php

namespace App\Repositories;

use App\Models\Resource;
use App\Repositories\Contracts\ResourceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ResourceRepository implements ResourceRepositoryInterface
{
    public function findResourcesByTenantId(int $tenantId): Collection
    {
        return Resource::query()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function findResourcesByTenantLocationId(int $tenantLocationId): Collection
    {
        return Resource::query()
            ->where('tenant_location_id', $tenantLocationId)
            ->get();
    }

    public function findResourceByIdAndTenantId(int $resourceId, int $tenantId): ?Resource
    {
        return Resource::query()
            ->where('id', $resourceId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function updateOrCreateResource(array $where, array $data): Resource
    {
        return Resource::query()->updateOrCreate($where, $data);
    }
}
