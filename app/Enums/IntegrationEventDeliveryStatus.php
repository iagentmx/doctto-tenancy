<?php

namespace App\Enums;

use App\Enums\Traits\HasStringBackedEnum;

enum IntegrationEventDeliveryStatus: string
{
    use HasStringBackedEnum;

    case Pending = 'pending';
    case Processing = 'processing';
    case Delivered = 'delivered';
    case Failed = 'failed';
}
