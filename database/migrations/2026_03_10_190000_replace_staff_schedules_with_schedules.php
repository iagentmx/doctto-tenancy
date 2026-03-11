<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('schedulable_type', 32);
            $table->unsignedBigInteger('schedulable_id');
            $table->foreignId('tenant_location_id')->constrained('tenant_locations')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->index([
                'tenant_id',
                'schedulable_type',
                'schedulable_id',
                'day_of_week',
                'start_time',
                'end_time',
            ], 'schedules_lookup_idx');

            $table->unique([
                'tenant_id',
                'schedulable_type',
                'schedulable_id',
                'tenant_location_id',
                'day_of_week',
                'start_time',
                'end_time',
            ], 'schedules_unique_row');
        });

        DB::statement("ALTER TABLE schedules ADD CONSTRAINT schedules_schedulable_type_check CHECK (schedulable_type IN ('staff', 'resource'))");

        if (Schema::hasTable('staff_schedules')) {
            DB::statement("
                INSERT INTO schedules (
                    tenant_id,
                    schedulable_type,
                    schedulable_id,
                    tenant_location_id,
                    day_of_week,
                    start_time,
                    end_time,
                    is_active,
                    created_at,
                    updated_at
                )
                SELECT
                    st.tenant_id,
                    'staff',
                    ss.staff_id,
                    ss.tenant_location_id,
                    ss.day_of_week,
                    ss.start_time,
                    ss.end_time,
                    ss.is_active,
                    ss.created_at,
                    ss.updated_at
                FROM staff_schedules ss
                INNER JOIN staff st ON st.id = ss.staff_id
            ");

            Schema::drop('staff_schedules');
        }
    }

    public function down(): void
    {
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $table->foreignId('tenant_location_id')->constrained('tenant_locations')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('is_active')->default(true);
            $table->timestampsTz();

            $table->unique(['staff_id', 'tenant_location_id', 'day_of_week', 'start_time', 'end_time']);
        });

        DB::statement("
            INSERT INTO staff_schedules (
                staff_id,
                tenant_location_id,
                day_of_week,
                start_time,
                end_time,
                is_active,
                created_at,
                updated_at
            )
            SELECT
                schedulable_id,
                tenant_location_id,
                day_of_week,
                start_time,
                end_time,
                is_active,
                created_at,
                updated_at
            FROM schedules
            WHERE schedulable_type = 'staff'
        ");

        Schema::dropIfExists('schedules');
    }
};
