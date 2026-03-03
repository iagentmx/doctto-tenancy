<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping;

use App\Enums\OperationType;

final class AccountToTenantMapper
{
    /**
     * Mantiene exactamente el mismo mapping que antes (extraído del service original).
     * Devuelve:
     *  - uniqueKeys: para updateOrCreateTenant
     *  - tenantData: payload normalizado
     */
    public static function map(array $payload): array
    {
        $uniqueKeys = [
            'espocrm_id' => $payload['id'] ?? null,
        ];

        // Construir dirección (igual que antes)
        $street  = $payload['billingAddressStreet']     ?? null;
        $city    = $payload['billingAddressCity']       ?? null;
        $state   = $payload['billingAddressState']      ?? null;
        $country = $payload['billingAddressCountry']    ?? null;
        $postal  = $payload['billingAddressPostalCode'] ?? null;

        $fullAddress = '';

        if ($street) {
            $fullAddress .= $street . '.';
        }

        if ($city && $state) {
            $fullAddress .= ($fullAddress ? ' ' : '') . $city . ', ' . $state . '.';
        } elseif ($city) {
            $fullAddress .= ($fullAddress ? ' ' : '') . $city . '.';
        } elseif ($state) {
            $fullAddress .= ($fullAddress ? ' ' : '') . $state . '.';
        }

        if ($country) {
            $fullAddress .= ($fullAddress ? ' ' : '') . $country;
        }

        if ($postal) {
            $fullAddress .= ($fullAddress ? ' ' : '') . 'C.P. ' . $postal;
        }

        if ($fullAddress === '') {
            $fullAddress = null;
        }

        $timeZone = $payload['cTimeZone'] ?? null;
        if (is_string($timeZone)) {
            $timeZone = trim($timeZone);
        } else {
            $timeZone = null;
        }

        $operationType = OperationType::SingleStaff->value;
        if (isset($payload['operationType']) && is_string($payload['operationType'])) {
            $candidate = OperationType::tryFrom(trim($payload['operationType']));
            if ($candidate instanceof OperationType) {
                $operationType = $candidate->value;
            }
        }

        $tenantData = [
            'espocrm_id'    => $payload['id'] ?? null,
            'name'          => $payload['name'] ?? 'Sin nombre',
            'is_active'     => $payload['cIsActive'] ?? true,
            'jid'           => $payload['cJid'] ?? null,
            'industry_type' => $payload['industry'] ?? null,
            'operation_type' => $operationType,
            'description'   => $payload['description'] ?? null,
            'settings'      => [
                'assistantName' => $payload['cAssistantName'] ?? 'Sofía',
                'urlReviewPlatform' => $payload['cReviewPlatformUrl'] ?? null,
                'calCom' => [
                    'user' => $payload['cUserCal'] ?? null,
                    'token' => $payload['cTokenCal'] ?? null,
                ],
                'features' => [
                    'surveysEnabled' => (bool) ($payload['cReviewEnabled'] ?? false),
                    'billingEnabled' => false,
                ],
            ],
        ];

        $tenantLocationData = [
            'name' => 'Matriz',
            'address' => $fullAddress,
            'time_zone' => $timeZone,
            'url_map' => $payload['cMap'] ?? null,
            'is_primary' => true,
            'is_active' => (bool) ($payload['cIsActive'] ?? true),
            'settings' => [],
        ];

        return [
            'uniqueKeys' => $uniqueKeys,
            'tenantData' => $tenantData,
            'tenantLocationData' => $tenantLocationData,
        ];
    }
}
