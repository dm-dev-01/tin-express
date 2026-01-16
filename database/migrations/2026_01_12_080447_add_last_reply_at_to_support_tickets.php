<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->timestamp('last_reply_at')->nullable()->after('priority');
        });

        // Data Migration: Set initial last_reply_at to created_at for existing tickets
        DB::statement('UPDATE support_tickets SET last_reply_at = created_at');
    }

    public function down(): void
    {
        Schema::table('support_tickets', function (Blueprint $table) {
            $table->dropColumn('last_reply_at');
        });
    }
};