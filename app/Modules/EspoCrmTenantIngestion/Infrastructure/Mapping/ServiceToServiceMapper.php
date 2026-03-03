<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping;

final class ServiceToServiceMapper
{
    /**
     * Mantiene el mismo mapping del service original.
     * Devuelve:
     *  - categoryCriteria/categoryData
     *  - serviceCriteria/serviceData
     */
    public static function map(int $tenantId, int $categoryId, array $payload): array
    {
        $serviceCriteria = [
            'tenant_id'  => $tenantId,
            'espocrm_id' => $payload['id'],
        ];

        $serviceData = [
            'tenant_id'        => $tenantId,
            'espocrm_id'       => $payload['id'],
            'name'             => $payload['name'],
            'description'      => $payload['description'] ?? null,
            'duration_minutes' => $payload['duration'],
            'price'            => $payload['price'] ?? 0,
            'category_id'      => $categoryId,
            'is_active'        => $payload['isActive'] ?? true,
        ];

        return [
            'serviceCriteria' => $serviceCriteria,
            'serviceData'     => $serviceData,
        ];
    }

    public static function categoryCriteria(int $tenantId, array $payload): array
    {
        return [
            'tenant_id' => $tenantId,
            'name'      => $payload['category'],
        ];
    }

    public static function categoryData(int $tenantId, array $payload): array
    {
        return [
            'tenant_id' => $tenantId,
            'name'      => $payload['category'],
        ];
    }
}
