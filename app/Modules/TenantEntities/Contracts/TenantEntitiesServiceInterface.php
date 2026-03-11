<?php

namespace App\Modules\TenantEntities\Contracts;

use App\Modules\TenantEntities\DTO\TenantAdminData;

interface TenantEntitiesServiceInterface
{
    public function getByJid(string $jid): array;

    public function getCatalogByTenantId(int $tenantId): array;

    public function getByEspoCrmId(string $espocrmId): array;

    public function registerTenantAdmin(TenantAdminData $tenantAdminData): array;
}
