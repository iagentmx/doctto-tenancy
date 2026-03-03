<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_locations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('time_zone', 64)->nullable();
            $table->text('url_map')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();

            $table->unique(['tenant_id', 'name']);
        });

        DB::statement('CREATE UNIQUE INDEX tenant_locations_one_primary_per_tenant_idx ON tenant_locations (tenant_id) WHERE is_primary = true');
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_locations');
    }
};
