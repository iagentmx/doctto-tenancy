<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\ServiceToServiceMapper;
use App\Models\ServiceCategory;
use App\Repositories\Contracts\ServiceCategoryRepositoryInterface;

final class UpsertServiceCategoryUseCase
{
    public function __construct(
        protected ServiceCategoryRepositoryInterface $serviceCategoryRepository
    ) {}

    public function execute(int $tenantId, array $payload): ServiceCategory
    {
        $criteria = ServiceToServiceMapper::categoryCriteria($tenantId, $payload);
        $data     = ServiceToServiceMapper::categoryData($tenantId, $payload);

        return $this->serviceCategoryRepository->updateOrCreateServiceCategory($criteria, $data);
    }
}
