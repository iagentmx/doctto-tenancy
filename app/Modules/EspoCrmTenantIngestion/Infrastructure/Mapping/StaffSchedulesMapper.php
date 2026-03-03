<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping;

use App\Exceptions\EspoCrmWebhookException;

final class StaffSchedulesMapper
{
    /**
     * Mantiene exactamente el mismo día->número y llaves del payload.
     * Devuelve una lista de rows listas para createStaffSchedule().
     */
    public static function map(int $staffId, int $tenantLocationId, array $payload): array
    {
        $rows = [];

        $dayMap = [
            'monday'    => 1,
            'tuesday'   => 2,
            'wednesday' => 3,
            'thursday'  => 4,
            'friday'    => 5,
            'saturday'  => 6,
            'sunday'    => 7,
        ];

        foreach ($dayMap as $day => $dayNumber) {
            $enabled = $payload[$day . 'Enabled'] ?? false;

            if ($enabled) {
                $startTime = $payload[$day . 'Start'] ?? null;
                $endTime = $payload[$day . 'End'] ?? null;

                if (!is_string($startTime) || trim($startTime) === '' || !is_string($endTime) || trim($endTime) === '') {
                    throw new EspoCrmWebhookException("Payload inválido: {$day}Enabled=true requiere {$day}Start y {$day}End.", 422);
                }

                $rows[] = [
                    'staff_id' => $staffId,
                    'tenant_location_id' => $tenantLocationId,
                    'day_of_week' => $dayNumber,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'is_active' => true,
                ];
            }
        }

        return $rows;
    }
}
