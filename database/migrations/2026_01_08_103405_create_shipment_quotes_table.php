<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('shipment_quotes', function (Blueprint $table) {
        $table->id();
        $table->foreignId('shipment_id')->constrained()->onDelete('cascade');
        
        $table->string('courier_name'); // e.g., "Fastway"
        $table->string('service_name'); // e.g., "Overnight Satchel"
        $table->string('service_code'); // e.g., "FW_ON_SATCHEL" (needed for API booking)
        
        $table->integer('price_cents'); // Store as integer (dollars * 100)
        $table->string('eta')->nullable(); // e.g., "1-2 Days"
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_quotes');
    }
};
