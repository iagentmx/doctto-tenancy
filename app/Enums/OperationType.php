<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum OperationType: string
{
    use HasStringBackedEnum;

    case SingleStaff = 'single_staff';
    case MultiStaff = 'multi_staff';
    case MultiResource = 'multi_resource';
    case MultiLocation = 'multi_location';
}
