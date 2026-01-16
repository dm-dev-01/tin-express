<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            
            // Identity
            $table->string('abn')->unique(); // Australian Business Number
            $table->string('abn_status')->default('Active'); 
            $table->string('entity_name'); // Official name from ABR Lookup
            $table->string('trading_name')->nullable();
            
            // Location
            $table->string('address_line_1')->nullable();
            $table->string('suburb')->nullable();
            $table->string('state')->nullable();
            $table->string('postcode')->nullable();
            $table->char('country_code', 2)->default('AU'); // ISO Alpha-2
            
            // Settings
            $table->char('currency', 3)->default('AUD');
            $table->string('timezone')->default('Australia/Sydney');
            
            // Financials
            $table->bigInteger('wallet_balance')->default(0); 
            $table->string('billing_email');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};