<?php

namespace App\Repositories;

use App\Models\Tenant;
use App\Repositories\Contracts\TenantRepositoryInterface;

class TenantRepository implements TenantRepositoryInterface
{
    private function catalogRelations(): array
    {
        return [
            'primaryLocation',
            'tenantLocations',
            'services.category',
            'serviceCategories',
            'staff.schedules.tenantLocation',
            'staff.services',
            'resources.resourceType',
            'resources.schedules.tenantLocation',
            'tenantAdmins',
        ];
    }

    public function findTenantByJid(string $jid): ?Tenant
    {
        return Tenant::query()->with([
            'services',
            'staff',
            'staff.schedules',
            'staff.services',
            'primaryLocation',
        ])
            ->where('jid', $jid)
            ->first();
    }

    public function findTenantById(int $tenantId): ?Tenant
    {
        return Tenant::query()
            ->with($this->catalogRelations())
            ->where('id', $tenantId)
            ->first();
    }

    public function findTenantByEspoCrmId(string $espocrmId): ?Tenant
    {
        return Tenant::query()->where('espocrm_id', $espocrmId)->first();
    }

    public function updateOrCreateTenant(array $uniqueKeys, array $data): Tenant
    {
        return Tenant::query()->updateOrCreate($uniqueKeys, $data);
    }

    public function updateTenant(int $tenantId, array $data): Tenant
    {
        $tenant = Tenant::query()->findOrFail($tenantId);
        $tenant->fill($data);
        $tenant->save();

        return $tenant;
    }

    public function existsTenantByJid(string $jid): bool
    {
        return Tenant::query()->where('jid', $jid)->exists();
    }
}
