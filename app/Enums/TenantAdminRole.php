<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum TenantAdminRole: string
{
    use HasStringBackedEnum;

    case Owner = 'owner';
    case Admin = 'admin';
}
