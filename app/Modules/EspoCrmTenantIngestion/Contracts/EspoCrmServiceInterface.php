<?php

namespace App\Modules\EspoCrmTenantIngestion\Contracts;

interface EspoCrmServiceInterface
{
    public function handleAccountUpdated(array $payload): array;

    public function handleOpportunityUpdated(array $payload): array;

    public function handleServiceCreated(array $payload): array;

    public function handleServiceUpdated(array $payload): array;

    public function handleStaffCreated(array $payload): array;

    public function handleStaffUpdated(array $payload): array;
}
