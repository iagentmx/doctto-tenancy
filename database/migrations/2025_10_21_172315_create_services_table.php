<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('espocrm_id', 64)->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('duration_minutes')->default(30);
            $table->decimal('price', 12, 2)->default(0);
            $table->foreignId('category_id')->nullable()->constrained('service_categories')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();

            $table->unique(['tenant_id', 'name']);
            $table->unique(['tenant_id', 'espocrm_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
