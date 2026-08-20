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
            if (! Schema::hasColumn('public_booking_requests', 'adults')) {
                $table->unsignedTinyInteger('adults')->nullable()->after('passengers');
            }

            if (! Schema::hasColumn('public_booking_requests', 'children')) {
                $table->unsignedTinyInteger('children')->nullable()->after('adults');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table): void {
            if (Schema::hasColumn('public_booking_requests', 'children')) {
                $table->dropColumn('children');
            }

            if (Schema::hasColumn('public_booking_requests', 'adults')) {
                $table->dropColumn('adults');
            }
        });
    }
};
