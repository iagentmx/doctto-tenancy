<?php

namespace App\Repositories\Contracts;

use App\Models\ServiceCategory;

interface ServiceCategoryRepositoryInterface
{
    public function findServiceCategoryByTenantAndName(int $tenantId, string $name): ?ServiceCategory;

    public function updateOrCreateServiceCategory(array $where, array $data): ServiceCategory;
}
