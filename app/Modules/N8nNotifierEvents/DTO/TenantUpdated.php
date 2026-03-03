<?php

namespace App\Modules\N8nNotifierEvents\DTO;

class TenantUpdated
{
    public function __construct(
        public string $tenantJid
    ) {}
}
