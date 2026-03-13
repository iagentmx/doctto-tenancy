<?php

namespace App\Http\Requests\Concerns;

trait HasTenantStaffRouteValidation
{
    protected function tenantJidRules(): array
    {
        return [
            'required',
            'string',
            'regex:/^521\d{10}@s\.whatsapp\.net$/',
        ];
    }

    protected function staffIdRules(): array
    {
        return [
            'required',
            'integer',
            'min:1',
        ];
    }

    protected function prepareTenantJidValidation(): void
    {
        $this->merge([
            'tenant_jid' => $this->route('tenantJid'),
        ]);
    }

    protected function prepareTenantJidAndStaffIdValidation(): void
    {
        $this->merge([
            'tenant_jid' => $this->route('tenantJid'),
            'staff_id' => $this->route('staffId'),
        ]);
    }
}
