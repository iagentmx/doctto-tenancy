<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum TenantAdminChannelType: string
{
    use HasStringBackedEnum;

    case WhatsApp = 'whatsapp';
    case Phone = 'phone';
    case Email = 'email';
}
