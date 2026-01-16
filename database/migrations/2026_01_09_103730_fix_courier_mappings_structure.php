<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courier_mappings', function (Blueprint $table) {
            // 1. Add 'name_in_app' if missing
            if (!Schema::hasColumn('courier_mappings', 'name_in_app')) {
                $table->string('name_in_app')->nullable()->after('id');
            }
            
            // 2. Add 'carrier_config_id' if missing
            if (!Schema::hasColumn('courier_mappings', 'carrier_config_id')) {
                $table->unsignedBigInteger('carrier_config_id')->nullable()->after('name_in_app');
            }

            // 3. FIX: Add 'is_active' if missing (This caused your error)
            if (!Schema::hasColumn('courier_mappings', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('carrier_config_id');
            }

            // 4. Make existing columns nullable so we can insert partial data
            // This prevents the "Field 'courier_code' doesn't have a default value" error
            if (Schema::hasColumn('courier_mappings', 'courier_code')) {
                $table->string('courier_code')->nullable()->change();
            }
            if (Schema::hasColumn('courier_mappings', 'target_field')) {
                $table->string('target_field')->nullable()->change();
            }
            if (Schema::hasColumn('courier_mappings', 'input_value')) {
                $table->string('input_value')->nullable()->change();
            }
            if (Schema::hasColumn('courier_mappings', 'output_code')) {
                $table->string('output_code')->nullable()->change();
            }
            if (Schema::hasColumn('courier_mappings', 'output_description')) {
                $table->string('output_description')->nullable()->change();
            }
        });

        // Clear old data to prevent duplicates, but DO NOT seed here.
        // Seeding happens in CourierMappingSeeder.php now.
        DB::table('courier_mappings')->truncate();
    }

    public function down(): void
    {
        // No down action needed for dev fixes
    }
};