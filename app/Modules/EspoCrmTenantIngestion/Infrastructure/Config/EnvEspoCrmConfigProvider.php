<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Config;

use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmConfigProviderInterface;

class EnvEspoCrmConfigProvider implements EspoCrmConfigProviderInterface
{
    public function baseUrl(): string
    {
        return (string) config('espocrm.base_url');
    }

    public function username(): string
    {
        return (string) config('espocrm.username');
    }

    public function password(): string
    {
        return (string) config('espocrm.password');
    }

    public function timeoutSeconds(): int
    {
        return (int) config('espocrm.timeout_seconds', 10);
    }
}
