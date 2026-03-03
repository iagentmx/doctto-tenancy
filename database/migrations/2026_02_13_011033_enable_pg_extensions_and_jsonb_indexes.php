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

        // Extensiones necesarias
        DB::statement("CREATE EXTENSION IF NOT EXISTS pgcrypto");
        DB::statement("CREATE EXTENSION IF NOT EXISTS unaccent");
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm");

        // Índices GIN para JSONB
        DB::statement("CREATE INDEX IF NOT EXISTS idx_tenants_settings_gin ON tenants USING GIN (settings)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_services_settings_gin ON services USING GIN (settings)");
        DB::statement("CREATE INDEX IF NOT EXISTS idx_staff_settings_gin ON staff USING GIN (settings)");

        /**
         * Postgres no permite usar unaccent() directo en índices porque es STABLE.
         * Workaround: wrapper IMMUTABLE con diccionario fijo.
         */
        DB::statement(<<<'SQL'
CREATE OR REPLACE FUNCTION immutable_unaccent(text)
RETURNS text
LANGUAGE sql
IMMUTABLE
STRICT
PARALLEL SAFE
AS $$
  SELECT unaccent('unaccent', $1)
$$;
SQL);
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement("DROP INDEX IF EXISTS idx_staff_settings_gin");
        DB::statement("DROP INDEX IF EXISTS idx_services_settings_gin");
        DB::statement("DROP INDEX IF EXISTS idx_tenants_settings_gin");

        DB::statement("DROP FUNCTION IF EXISTS immutable_unaccent(text)");
    }
};
