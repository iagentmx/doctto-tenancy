<?php

namespace App\Modules\EspoCrmTenantIngestion\Contracts;

interface EspoCrmConfigProviderInterface
{
    public function baseUrl(): string;

    public function username(): string;

    public function password(): string;

    public function timeoutSeconds(): int;
}
