<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping;

final class StaffToStaffMapper
{
    /**
     * Mantiene el mismo mapping del service original para Staff.
     */
    public static function map(int $tenantId, array $payload): array
    {
        $criteria = [
            'tenant_id'  => $tenantId,
            'espocrm_id' => $payload['id'],
        ];

        $data = [
            'tenant_id'  => $tenantId,
            'espocrm_id' => $payload['id'],
            'name'       => $payload['name'] ?? 'Sin nombre',
            'role'       => $payload['role'] ?? null,
            'phone'      => $payload['phone'] ?? null,
            'email'      => $payload['email'] ?? null,
            'is_active'  => $payload['active'] ?? true,
            'settings'   => [
                'specialty' => $payload['specialty'] ?? null,
                'about'     => $payload['about'] ?? null,
            ],
        ];

        return [
            'criteria' => $criteria,
            'data'     => $data,
        ];
    }
}
