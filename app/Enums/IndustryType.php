<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum IndustryType: string
{
    use HasStringBackedEnum;

    case Healthcare = 'Healthcare';
    case Other = 'Other';
}
