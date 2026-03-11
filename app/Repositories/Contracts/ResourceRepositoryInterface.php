<?php

namespace App\Repositories\Contracts;

use App\Models\Resource;
use Illuminate\Database\Eloquent\Collection;

interface ResourceRepositoryInterface
{
    public function findResourcesByTenantId(int $tenantId): Collection;
    public function findResourcesByTenantLocationId(int $tenantLocationId): Collection;
    public function findResourceByIdAndTenantId(int $resourceId, int $tenantId): ?Resource;
    public function updateOrCreateResource(array $where, array $data): Resource;
}
