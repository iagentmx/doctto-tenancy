<?php

namespace App\Repositories\Contracts;

use App\Models\Tenant;

interface TenantRepositoryInterface
{
    public function findTenantByJid(string $jid): ?Tenant; //
    public function findTenantByEspoCrmId(string $espocrmId): ?Tenant; //
    public function updateOrCreateTenant(array $uniqueKeys, array $data): Tenant; //
    public function updateTenant(int $tenantId, array $data): Tenant;
    public function existsTenantByJid(string $jid): bool;
}
