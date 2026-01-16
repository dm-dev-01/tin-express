<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Shipments (The Header)
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            // Link to Company & User
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Sender Details (These were missing!)
            $table->string('sender_name');
            $table->string('sender_address')->nullable(); // <--- Added
            $table->string('sender_suburb');              // <--- Made required
            $table->string('sender_state');               // <--- Added
            $table->string('sender_postcode');

            // Receiver Details
            $table->string('receiver_name');
            $table->string('receiver_address');
            $table->string('receiver_suburb');
            $table->string('receiver_state');
            $table->string('receiver_postcode');

            // Logistics Status
            $table->string('status')->default('draft');
            
            // Booking Details (Nullable for drafts)
            $table->string('courier_name')->nullable();
            $table->string('service_name')->nullable();      // <--- Added
            $table->integer('total_price_cents')->nullable(); // <--- Added
            $table->string('tracking_number')->nullable();

            $table->timestamps();
        });

        // 2. Shipment Items (The Rows)
        Schema::create('shipment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();

            $table->string('type')->default('box');
            $table->decimal('length', 10, 2); // Changed to decimal for precision
            $table->decimal('width', 10, 2);
            $table->decimal('height', 10, 2);
            $table->decimal('weight', 10, 2);
            $table->integer('quantity')->default(1);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_items');
        Schema::dropIfExists('shipments');
    }
};