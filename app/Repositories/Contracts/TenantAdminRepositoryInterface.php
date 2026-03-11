<?php

namespace App\Repositories\Contracts;

use App\Models\TenantAdmin;
use Illuminate\Database\Eloquent\Collection;

interface TenantAdminRepositoryInterface
{
    public function findTenantAdminsByTenantId(int $tenantId): Collection;
    public function findTenantAdminByTenantChannelTypeAndJid(int $tenantId, string $channelType, string $jid): ?TenantAdmin;
    public function findOwnerTenantAdminByTenantId(int $tenantId): ?TenantAdmin;
    public function updateOrCreateTenantAdmin(array $where, array $data): TenantAdmin;
}
