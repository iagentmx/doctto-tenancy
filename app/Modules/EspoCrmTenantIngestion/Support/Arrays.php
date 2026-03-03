<?php

namespace App\Modules\EspoCrmTenantIngestion\Support;

final class Arrays
{
    public static function get(array $arr, string $key, $default = null)
    {
        return array_key_exists($key, $arr) ? $arr[$key] : $default;
    }
}
