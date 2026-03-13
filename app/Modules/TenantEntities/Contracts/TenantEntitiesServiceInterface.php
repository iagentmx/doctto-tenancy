<?php

namespace App\Modules\TenantEntities\Contracts;

use App\Modules\TenantEntities\DTO\TenantAdminData;
use App\Modules\TenantEntities\DTO\StaffData;

interface TenantEntitiesServiceInterface
{
    public function getByJid(string $jid): array;

    public function getCatalogByTenantId(int $tenantId): array;

    public function getByEspoCrmId(string $espocrmId): array;

    public function registerTenantAdmin(TenantAdminData $tenantAdminData): array;

    public function listStaffByTenantJid(string $tenantJid): array;

    public function getStaffByTenantJidAndId(string $tenantJid, int $staffId): array;

    public function createStaff(string $tenantJid, StaffData $staffData): array;

    public function updateStaff(string $tenantJid, int $staffId, StaffData $staffData): array;

    public function deleteStaff(string $tenantJid, int $staffId): void;
}
