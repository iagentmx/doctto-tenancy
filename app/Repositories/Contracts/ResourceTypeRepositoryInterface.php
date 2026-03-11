<?php

namespace App\Repositories\Contracts;

use App\Models\ResourceType;
use Illuminate\Database\Eloquent\Collection;

interface ResourceTypeRepositoryInterface
{
    public function allResourceTypes(): Collection;
    public function findResourceTypeById(int $resourceTypeId): ?ResourceType;
    public function findResourceTypeByName(string $name): ?ResourceType;
    public function updateOrCreateResourceType(array $where, array $data): ResourceType;
}
