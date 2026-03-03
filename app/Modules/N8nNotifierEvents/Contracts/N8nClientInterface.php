<?php

namespace App\Modules\N8nNotifierEvents\Contracts;

interface N8nClientInterface
{
    public function postUpdateTenantWebhook(array $payload): void;
}
