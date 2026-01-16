<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Table to store API Keys
        Schema::create('user_integrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // Add this line (it was missing in some versions)
            $table->string('platform'); // 'shopify'
            $table->string('store_url'); 
            $table->string('api_secret')->nullable(); // Encrypted Access Token
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        // 2. Add columns to shipments table (if not already added)
        if (!Schema::hasColumn('shipments', 'source')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('source')->default('manual'); // 'manual', 'shopify'
                $table->string('external_order_id')->nullable(); 
                $table->string('external_order_number')->nullable(); 
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_integrations');
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['source', 'external_order_id', 'external_order_number']);
        });
    }
};