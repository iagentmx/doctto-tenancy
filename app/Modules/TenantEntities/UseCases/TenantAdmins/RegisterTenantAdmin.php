<?php

namespace App\Modules\TenantEntities\UseCases\TenantAdmins;

use App\Enums\TenantAdminRole;
use App\Exceptions\ApiServiceException;
use App\Models\TenantAdmin;
use App\Modules\TenantEntities\DTO\TenantAdminData;
use App\Repositories\Contracts\TenantAdminRepositoryInterface;

final class RegisterTenantAdmin
{
    public function __construct(
        protected TenantAdminRepositoryInterface $tenantAdminRepository,
    ) {}

    public function execute(TenantAdminData $tenantAdminData): TenantAdmin
    {
        $existingTenantAdmin = $this->tenantAdminRepository->findTenantAdminByTenantChannelTypeAndJid(
            $tenantAdminData->tenantId,
            $tenantAdminData->channelType,
            $tenantAdminData->jid,
        );

        $ownerTenantAdmin = $this->tenantAdminRepository->findOwnerTenantAdminByTenantId(
            $tenantAdminData->tenantId
        );

        $resolvedRole = $this->resolveRole(
            $tenantAdminData,
            $existingTenantAdmin,
            $ownerTenantAdmin,
        );

        return $this->tenantAdminRepository->updateOrCreateTenantAdmin(
            $tenantAdminData->uniqueKeys(),
            $tenantAdminData->toRepositoryData($resolvedRole),
        );
    }

    private function resolveRole(
        TenantAdminData $tenantAdminData,
        ?TenantAdmin $existingTenantAdmin,
        ?TenantAdmin $ownerTenantAdmin,
    ): string {
        $requestedRole = $tenantAdminData->role ?? TenantAdminRole::Admin->value;

        if (! $ownerTenantAdmin) {
            return TenantAdminRole::Owner->value;
        }

        if (
            $existingTenantAdmin &&
            $ownerTenantAdmin->is($existingTenantAdmin) &&
            $requestedRole !== TenantAdminRole::Owner->value
        ) {
            throw new ApiServiceException(
                'No se puede remover el owner del tenant sin un flujo explícito de transferencia.',
                409
            );
        }

        if (
            $requestedRole === TenantAdminRole::Owner->value &&
            (! $existingTenantAdmin || ! $ownerTenantAdmin->is($existingTenantAdmin))
        ) {
            throw new ApiServiceException(
                'El tenant ya cuenta con un owner activo.',
                409
            );
        }

        return $existingTenantAdmin?->role?->value === TenantAdminRole::Owner->value
            ? TenantAdminRole::Owner->value
            : $requestedRole;
    }
}
