<?php

namespace App\Modules\EspoCrmTenantIngestion\Exceptions;

use App\Exceptions\ApiServiceException;
use Throwable;

class EspoCrmTenantIngestionException extends ApiServiceException
{
    public function __construct(string $message, int $statusCode = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
    }
}
