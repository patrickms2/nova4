<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasTable('public_booking_requests') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE public_booking_requests MODIFY type ENUM('hotel', 'taxi', 'restaurant', 'tour', 'transfer', 'package') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('public_booking_requests') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE public_booking_requests MODIFY type ENUM('hotel', 'taxi', 'restaurant', 'tour', 'transfer') NOT NULL");
    }
};
