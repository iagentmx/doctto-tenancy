<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum SchedulableType: string
{
    use HasStringBackedEnum;

    case Staff = 'staff';
    case Resource = 'resource';
}
