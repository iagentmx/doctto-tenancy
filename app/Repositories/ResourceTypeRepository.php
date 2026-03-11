<?php

namespace App\Repositories;

use App\Models\ResourceType;
use App\Repositories\Contracts\ResourceTypeRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ResourceTypeRepository implements ResourceTypeRepositoryInterface
{
    public function allResourceTypes(): Collection
    {
        return ResourceType::query()->get();
    }

    public function findResourceTypeById(int $resourceTypeId): ?ResourceType
    {
        return ResourceType::query()
            ->where('id', $resourceTypeId)
            ->first();
    }

    public function findResourceTypeByName(string $name): ?ResourceType
    {
        return ResourceType::query()
            ->where('name', $name)
            ->first();
    }

    public function updateOrCreateResourceType(array $where, array $data): ResourceType
    {
        return ResourceType::query()->updateOrCreate($where, $data);
    }
}
