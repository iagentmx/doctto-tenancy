<?php

namespace App\Modules\EspoCrmTenantIngestion\Support;

final class DateTime
{
    public static function nowIso(): string
    {
        return now()->toISOString();
    }
}
