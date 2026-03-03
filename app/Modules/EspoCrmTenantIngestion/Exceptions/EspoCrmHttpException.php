<?php

namespace App\Modules\EspoCrmTenantIngestion\Exceptions;

use Throwable;

class EspoCrmHttpException extends EspoCrmTenantIngestionException
{
    public function __construct(string $message = 'Error HTTP con EspoCRM', int $statusCode = 502, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
