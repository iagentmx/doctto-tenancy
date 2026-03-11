<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_admins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('channel_type', 32)->index();
            $table->string('jid', 191);
            $table->string('role', 32);
            $table->boolean('is_active')->default(true);
            $table->jsonb('settings')->default('{}');
            $table->timestampsTz();

            $table->unique(['tenant_id', 'channel_type', 'jid']);
        });

        DB::statement("
            CREATE UNIQUE INDEX tenant_admins_unique_owner_per_tenant
            ON tenant_admins (tenant_id)
            WHERE role = 'owner'
        ");
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS tenant_admins_unique_owner_per_tenant');
        Schema::dropIfExists('tenant_admins');
    }
};
