<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Store Credentials
        Schema::create('carrier_configs', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_code');
            $table->string('account_code')->nullable();
            
            // --- CHANGE THESE TWO LINES FROM string TO text ---
            $table->text('api_key')->nullable();    // Changed to text to hold encrypted string
            $table->text('api_secret')->nullable(); // Changed to text to hold encrypted string
            // --------------------------------------------------

            $table->string('environment')->default('test');
            $table->json('extra_settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Store Service Mappings (The "UI" Configuration Layer)
        Schema::create('carrier_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('carrier_code'); // e.g., 'northline'
            $table->string('our_value');    // e.g., 'pallet'
            $table->string('their_value');  // e.g., 'PAL'
            $table->string('field_type');   // e.g., 'package_type', 'service_code'
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('carrier_mappings');
        Schema::dropIfExists('carrier_configs');
    }
};