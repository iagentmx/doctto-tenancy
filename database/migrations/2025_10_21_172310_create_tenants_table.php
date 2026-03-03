<?php

use App\Enums\OperationType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('jid', length: 100)->unique();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->string('espocrm_id', 64)->nullable()->unique()->index();
            $table->string('industry_type', 32)->nullable();
            $table->string('operation_type', 32)->default(OperationType::SingleStaff->value);
            $table->text('description')->nullable();
            $table->jsonb('settings')->default(DB::raw("'{}'::jsonb"));
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
