<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE public_booking_requests MODIFY type ENUM('hotel', 'taxi', 'restaurant', 'tour') NOT NULL");
        }

        Schema::table('public_booking_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('public_booking_requests', 'participants')) {
                $table->unsignedTinyInteger('participants')->nullable()->after('passengers');
            }

            if (! Schema::hasColumn('public_booking_requests', 'tour_date')) {
                $table->date('tour_date')->nullable()->after('pickup_date_time');
            }

            if (! Schema::hasColumn('public_booking_requests', 'tour_schedule')) {
                $table->time('tour_schedule')->nullable()->after('tour_date');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('public_booking_requests')) {
            return;
        }

        Schema::table('public_booking_requests', function (Blueprint $table) {
            if (Schema::hasColumn('public_booking_requests', 'tour_schedule')) {
                $table->dropColumn('tour_schedule');
            }

            if (Schema::hasColumn('public_booking_requests', 'tour_date')) {
                $table->dropColumn('tour_date');
            }

            if (Schema::hasColumn('public_booking_requests', 'participants')) {
                $table->dropColumn('participants');
            }
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE public_booking_requests MODIFY type ENUM('hotel', 'taxi', 'restaurant') NOT NULL");
        }
    }
};
