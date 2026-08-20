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
        Schema::table('nova_bundle_orders', function (Blueprint $table) {
            $table->string('redsys_order', 50)->nullable()->after('factura_id');
            $table->string('payment_status', 30)->default('pending')->after('redsys_order');
            $table->json('payment_data')->nullable()->after('payment_status');
            $table->timestamp('paid_at')->nullable()->after('payment_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('nova_bundle_orders', function (Blueprint $table) {
            $table->dropColumn(['redsys_order', 'payment_status', 'payment_data', 'paid_at']);
        });
    }
};
