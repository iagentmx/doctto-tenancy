<?php

namespace App\Modules\TenantEntities\DTO;

final class StaffData
{
    public function __construct(
        public readonly int $tenantId,
        public readonly string $name,
        public readonly string $role,
        public readonly ?string $phone,
        public readonly ?string $email,
        public readonly bool $isActive,
        public readonly ?string $about,
        public readonly ?string $specialty,
    ) {}

    public function forTenant(int $tenantId): self
    {
        return new self(
            tenantId: $tenantId,
            name: $this->name,
            role: $this->role,
            phone: $this->phone,
            email: $this->email,
            isActive: $this->isActive,
            about: $this->about,
            specialty: $this->specialty,
        );
    }

    public function toRepositoryData(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'name' => $this->name,
            'role' => $this->role,
            'phone' => $this->phone,
            'email' => $this->email,
            'is_active' => $this->isActive,
            'settings' => [
                'about' => $this->about,
                'specialty' => $this->specialty,
            ],
        ];
    }
}
