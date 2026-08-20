<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('external_bookings')) {
            return;
        }

        Schema::table('external_bookings', function (Blueprint $table): void {
            if (! Schema::hasColumn('external_bookings', 'resource_type')) {
                $table->string('resource_type')->nullable()->after('booking_type');
            }

            if (! Schema::hasColumn('external_bookings', 'target_model')) {
                $table->string('target_model')->nullable()->after('resource_type');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('external_bookings')) {
            return;
        }

        Schema::table('external_bookings', function (Blueprint $table): void {
            if (Schema::hasColumn('external_bookings', 'target_model')) {
                $table->dropColumn('target_model');
            }

            if (Schema::hasColumn('external_bookings', 'resource_type')) {
                $table->dropColumn('resource_type');
            }
        });
    }
};

