<?php

namespace App\Repositories\Contracts;

interface StaffServiceRepositoryInterface
{
    public function syncServices(int $staffId, array $serviceIds): void;
}
