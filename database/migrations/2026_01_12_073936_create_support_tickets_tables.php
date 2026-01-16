<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. The Ticket Head
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Creator
            $table->foreignId('company_id')->constrained()->onDelete('cascade'); // Tenant Context
            $table->foreignId('shipment_id')->nullable()->constrained()->nullOnDelete(); // Optional Context
            
            $table->string('subject');
            $table->enum('status', ['open', 'in_progress', 'resolved', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            
            $table->timestamps();
        });

        // 2. The Messages (Chat Stream)
        Schema::create('ticket_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('support_ticket_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained(); // Sender (User or Admin)
            
            $table->text('message');
            $table->string('attachment_path')->nullable(); // For files/screenshots
            $table->string('attachment_name')->nullable(); // Original filename
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};