<?php

namespace App\Modules\EspoCrmTenantIngestion\Support;

final class Strings
{
    public static function trimOrNull($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $v = trim($value);

        return $v === '' ? null : $v;
    }
}
