<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        // Opcional: para generar UUIDs en la BD si algún día se requieren defaults como gen_random_uuid()
        DB::statement("CREATE EXTENSION IF NOT EXISTS pgcrypto");

        // Índices GIN para búsquedas en JSONB
        DB::statement("CREATE INDEX IF NOT EXISTS idx_tenants_settings_gin ON tenants USING GIN (settings)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_services_settings_gin ON services USING GIN (settings)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_staff_settings_gin ON staff USING GIN (settings)");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DROP INDEX IF EXISTS idx_staff_settings_gin");
        DB::statement("DROP INDEX IF EXISTS idx_services_settings_gin");
        DB::statement("DROP INDEX IF EXISTS idx_tenants_settings_gin");
    }
};
