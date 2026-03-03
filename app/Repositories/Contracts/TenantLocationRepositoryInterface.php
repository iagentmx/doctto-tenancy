<?php

namespace App\Repositories\Contracts;

use App\Models\TenantLocation;

interface TenantLocationRepositoryInterface
{
    public function updateOrCreatePrimaryTenantLocation(int $tenantId, array $data): TenantLocation;

    public function findPrimaryTenantLocationByTenantId(int $tenantId): ?TenantLocation;
}
