<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum StaffRole: string
{
    use HasStringBackedEnum;

    case Doctor = 'doctor';
    case Stylist = 'stylist';
    case Therapist = 'therapist';
    case Mechanic = 'mechanic';
    case Consultant = 'consultant';
}
