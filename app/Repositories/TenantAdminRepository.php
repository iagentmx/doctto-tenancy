<?php

namespace App\Repositories;

use App\Enums\TenantAdminRole;
use App\Models\TenantAdmin;
use App\Repositories\Contracts\TenantAdminRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class TenantAdminRepository implements TenantAdminRepositoryInterface
{
    public function findTenantAdminsByTenantId(int $tenantId): Collection
    {
        return TenantAdmin::query()
            ->where('tenant_id', $tenantId)
            ->get();
    }

    public function findTenantAdminByTenantChannelTypeAndJid(int $tenantId, string $channelType, string $jid): ?TenantAdmin
    {
        return TenantAdmin::query()
            ->where('tenant_id', $tenantId)
            ->where('channel_type', $channelType)
            ->where('jid', $jid)
            ->first();
    }

    public function findOwnerTenantAdminByTenantId(int $tenantId): ?TenantAdmin
    {
        return TenantAdmin::query()
            ->where('tenant_id', $tenantId)
            ->where('role', TenantAdminRole::Owner->value)
            ->first();
    }

    public function updateOrCreateTenantAdmin(array $where, array $data): TenantAdmin
    {
        return TenantAdmin::query()->updateOrCreate($where, $data);
    }
}
