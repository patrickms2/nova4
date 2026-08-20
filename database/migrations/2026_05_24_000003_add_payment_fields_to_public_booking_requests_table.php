<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table): void {
            if (! Schema::hasColumn('public_booking_requests', 'payment_provider')) {
                $table->string('payment_provider')->nullable()->after('remote_error');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_provider');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_amount_cents')) {
                $table->unsignedInteger('payment_amount_cents')->nullable()->after('payment_status');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_order')) {
                $table->string('payment_order')->nullable()->after('payment_amount_cents');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_reference')) {
                $table->string('payment_reference')->nullable()->after('payment_order');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_paid_at')) {
                $table->timestamp('payment_paid_at')->nullable()->after('payment_reference');
            }
            if (! Schema::hasColumn('public_booking_requests', 'payment_raw')) {
                $table->json('payment_raw')->nullable()->after('payment_paid_at');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table): void {
            foreach (['payment_raw', 'payment_paid_at', 'payment_reference', 'payment_order', 'payment_amount_cents', 'payment_status', 'payment_provider'] as $column) {
                if (Schema::hasColumn('public_booking_requests', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};

