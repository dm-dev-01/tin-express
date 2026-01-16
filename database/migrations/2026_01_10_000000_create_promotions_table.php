<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // e.g., "SUMMER25"
            $table->string('description')->nullable();
            $table->enum('type', ['percentage', 'fixed']);
            $table->decimal('value', 8, 2); // 10.00 or 20.00
            
            // Constraints
            $table->unsignedInteger('max_uses')->nullable();
            $table->unsignedInteger('current_uses')->default(0);
            $table->decimal('min_spend_cents', 10, 0)->nullable();
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};