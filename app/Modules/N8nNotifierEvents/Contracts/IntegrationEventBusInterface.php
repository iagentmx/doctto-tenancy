<?php

namespace App\Modules\N8nNotifierEvents\Contracts;

use App\Models\Tenant;

interface IntegrationEventBusInterface
{
    public function publishTenantUpdated(Tenant $tenant): void;
}
