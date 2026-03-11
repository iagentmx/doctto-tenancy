<?php

namespace App\Modules\NotifierEvents\DTO;

class PublishResult
{
    public function __construct(
        public bool $successful,
        public ?int $statusCode = null,
        public ?string $responseBody = null,
    ) {}
}
