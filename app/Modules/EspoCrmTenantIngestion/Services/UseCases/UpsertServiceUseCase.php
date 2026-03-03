<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\ServiceToServiceMapper;
use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;

final class UpsertServiceUseCase
{
    public function __construct(
        protected ServiceRepositoryInterface $serviceRepository
    ) {}

    public function execute(int $tenantId, int $categoryId, array $payload): Service
    {
        $mapped = ServiceToServiceMapper::map($tenantId, $categoryId, $payload);

        return $this->serviceRepository->updateOrCreateService(
            $mapped['serviceCriteria'],
            $mapped['serviceData']
        );
    }
}
