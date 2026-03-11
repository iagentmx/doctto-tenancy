<?php

namespace App\Modules\TenantEntities\DTO;

use App\Enums\TenantAdminRole;

final class TenantAdminData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $channelType,
        public readonly string $jid,
        public readonly ?string $role = null,
        public readonly bool $isActive = true,
        public readonly array $settings = [],
    ) {}

    public function uniqueKeys(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'channel_type' => $this->channelType,
            'jid' => $this->jid,
        ];
    }

    public function toRepositoryData(?string $role = null): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'channel_type' => $this->channelType,
            'jid' => $this->jid,
            'role' => $role ?? $this->role ?? TenantAdminRole::Admin->value,
            'is_active' => $this->isActive,
            'settings' => $this->settings,
        ];
    }
}
