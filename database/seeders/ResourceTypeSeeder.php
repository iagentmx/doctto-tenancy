<?php

namespace Database\Seeders;

use App\Models\ResourceType;
use Illuminate\Database\Seeder;

class ResourceTypeSeeder extends Seeder
{
    public function run(): void
    {
        ResourceType::query()->updateOrCreate(
            ['name' => 'Quirofano'],
            ['is_active' => true]
        );

        ResourceType::query()->updateOrCreate(
            ['name' => 'Rayos x'],
            ['is_active' => true]
        );
    }
}
