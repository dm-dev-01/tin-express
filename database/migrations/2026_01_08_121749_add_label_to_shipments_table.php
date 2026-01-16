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
    Schema::table('shipments', function (Blueprint $table) {
        $table->string('label_url')->nullable()->after('tracking_number');
        $table->string('consignment_number')->nullable()->after('courier_name'); // API specific ID
    });
}

public function down(): void
{
    Schema::table('shipments', function (Blueprint $table) {
        $table->dropColumn(['label_url', 'consignment_number']);
    });
}
};
