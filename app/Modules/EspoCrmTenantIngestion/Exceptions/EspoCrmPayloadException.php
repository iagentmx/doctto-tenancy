<?php

namespace App\Modules\EspoCrmTenantIngestion\Exceptions;

use Throwable;

class EspoCrmPayloadException extends EspoCrmTenantIngestionException
{
    public function __construct(string $message = 'Payload inválido de EspoCRM', int $statusCode = 422, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
