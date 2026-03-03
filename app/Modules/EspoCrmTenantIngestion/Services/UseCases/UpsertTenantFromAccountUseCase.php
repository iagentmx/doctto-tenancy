<?php

namespace App\Modules\EspoCrmTenantIngestion\Services\UseCases;

use App\Exceptions\EspoCrmWebhookException;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\AccountToTenantMapper;
use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\TenantLocationRepositoryInterface;
use Illuminate\Support\Facades\DB;

final class UpsertTenantFromAccountUseCase
{
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository,
        protected TenantLocationRepositoryInterface $tenantLocationRepository,
    ) {}

    public function execute(array $payload): Tenant
    {
        $mapped = AccountToTenantMapper::map($payload);

        $uniqueKeys = $mapped['uniqueKeys'] ?? null;
        $tenantData = $mapped['tenantData'] ?? null;
        $tenantLocationData = $mapped['tenantLocationData'] ?? null;

        if (!is_array($uniqueKeys) || !is_array($tenantData) || !is_array($tenantLocationData)) {
            throw new EspoCrmWebhookException('Error interno: mapping de tenant inválido.', 500);
        }

        return DB::transaction(function () use ($uniqueKeys, $tenantData, $tenantLocationData): Tenant {
            $tenant = $this->tenantRepository->updateOrCreateTenant($uniqueKeys, $tenantData);

            $this->tenantLocationRepository->updateOrCreatePrimaryTenantLocation($tenant->id, $tenantLocationData);

            return $tenant->load('primaryLocation');
        });
    }

    public function executeUpdateExisting(int $tenantId, array $payload): Tenant
    {
        $mapped = AccountToTenantMapper::map($payload);

        $tenantData = $mapped['tenantData'] ?? null;
        $tenantLocationData = $mapped['tenantLocationData'] ?? null;

        if (!is_array($tenantData) || !is_array($tenantLocationData)) {
            throw new EspoCrmWebhookException('Error interno: mapping de tenant inválido.', 500);
        }

        return DB::transaction(function () use ($tenantId, $tenantData, $tenantLocationData): Tenant {
            $tenant = $this->tenantRepository->updateTenant($tenantId, $tenantData);

            $this->tenantLocationRepository->updateOrCreatePrimaryTenantLocation($tenantId, $tenantLocationData);

            return $tenant->load('primaryLocation');
        });
    }
}
