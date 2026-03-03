<?php

namespace App\Repositories;

use App\Models\ServiceCategory;
use App\Repositories\Contracts\ServiceCategoryRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServiceCategoryRepository implements ServiceCategoryRepositoryInterface
{

    public function findServiceCategoryByTenantAndName(int $tenantId, string $name): ?ServiceCategory
    {
        return ServiceCategory::where('tenant_id', $tenantId)
            ->where('name', $name)
            ->first();
    }

    public function updateOrCreateServiceCategory(array $where, array $data): ServiceCategory
    {
        return ServiceCategory::updateOrCreate($where, $data);
    }
}
