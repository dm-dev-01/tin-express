<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courier_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('courier_code')->index(); // e.g., 'hunters'
            $table->string('target_field')->default('item_type'); // e.g., 'item_type', 'service_code'

            // What the user/system sends (e.g., 'box')
            $table->string('input_value'); 

            // What the API needs
            $table->string('output_code');       // e.g., 'CTN'
            $table->string('output_description')->nullable(); // e.g., 'Carton'

            $table->unique(['courier_code', 'target_field', 'input_value'], 'unique_mapping');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_mappings');
    }
};