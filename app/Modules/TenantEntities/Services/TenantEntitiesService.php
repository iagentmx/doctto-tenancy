<?php

namespace App\Modules\TenantEntities\Services;

use App\Exceptions\ApiServiceException;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantEntitiesService implements TenantEntitiesServiceInterface
{
    public function __construct(
        protected TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * Map day number (1-7) to day name.
     */
    private function mapDayName(int $day): string
    {
        return [
            1 => 'lunes',
            2 => 'martes',
            3 => 'miércoles',
            4 => 'jueves',
            5 => 'viernes',
            6 => 'sábado',
            7 => 'domingo',
        ][$day] ?? 'unknown';
    }

    /**
     * Retrieve a tenant by its WhatsApp JID.
     */
    public function getByJid(string $jid): array
    {
        $tenant = $this->tenantRepository->findTenantByJid($jid);

        if (! $tenant) {
            throw new ApiServiceException('Tenant no encontrado', 404);
        }

        return [
            'id'            => $tenant->espocrm_id,
            'jid'           => $tenant->jid,
            'name'          => $tenant->name,
            'industry_type' => $tenant->industry_type?->value ?? null,
            'is_active'     => $tenant->is_active,
            'address'       => $tenant->primaryLocation?->address,
            'description'   => $tenant->description,
            'time_zone'     => $tenant->primaryLocation?->time_zone,
            'url_map'       => $tenant->primaryLocation?->url_map,
            'settings'      => $tenant->settings,

            // STAFF
            'staff' => $tenant->staff->map(function ($staff) {
                return [
                    'name'  => $staff->name,
                    'phone' => $staff->phone,
                    'email' => $staff->email,

                    // SETTINGS (solo specialty y about)
                    'settings' => [
                        'about'     => $staff->settings['about'] ?? null,
                        'specialty' => $staff->settings['specialty'] ?? null,
                    ],

                    // SCHEDULES
                    'schedules' => $staff->schedules->map(function ($sch) {
                        return [
                            'day'        => $this->mapDayName($sch->day_of_week),
                            'start_time' => $sch->start_time,
                            'end_time'   => $sch->end_time,
                        ];
                    })->values(),

                    // STAFF SERVICES
                    'services' => $staff->services->map(function ($service) {
                        return [
                            'name'             => $service->name,
                            'description'      => $service->description,
                            'duration_minutes' => $service->duration_minutes,
                            'price'            => $service->price,
                        ];
                    })->values(),
                ];
            })->values(),
        ];
    }

    /**
     * Retrieve a tenant by its EspoCRM ID.
     */
    public function getByEspoCrmId(string $espocrmId): array
    {
        $tenant = $this->tenantRepository->findTenantByEspoCrmId($espocrmId);

        if (! $tenant) {
            throw new ApiServiceException('Tenant not found for given EspoCRM ID.', 404);
        }

        return [
            'status' => 'success',
            'message' => 'Tenant found successfully.',
            'result' => $tenant->toArray(),
        ];
    }
}
