<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->foreignId('rental_property_id')->nullable()->after('property_id')->constrained('rental_properties')->nullOnDelete();
        });

        Schema::table('access_points', function (Blueprint $table): void {
            $table->foreignId('rental_property_id')->nullable()->after('property_id')->constrained('rental_properties')->nullOnDelete();
        });

        Schema::table('access_grants', function (Blueprint $table): void {
            $table->foreignId('rental_property_id')->nullable()->after('property_id')->constrained('rental_properties')->nullOnDelete();
        });

        Schema::table('automations', function (Blueprint $table): void {
            $table->foreignId('rental_property_id')->nullable()->after('property_id')->constrained('rental_properties')->nullOnDelete();
        });

        Schema::table('domotics_events', function (Blueprint $table): void {
            $table->foreignId('rental_property_id')->nullable()->after('property_id')->constrained('rental_properties')->nullOnDelete();
        });

        $this->backfillRentalPropertyIds();
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table): void {
            $table->dropForeign(['rental_property_id']);
            $table->dropColumn('rental_property_id');
        });

        Schema::table('access_points', function (Blueprint $table): void {
            $table->dropForeign(['rental_property_id']);
            $table->dropColumn('rental_property_id');
        });

        Schema::table('access_grants', function (Blueprint $table): void {
            $table->dropForeign(['rental_property_id']);
            $table->dropColumn('rental_property_id');
        });

        Schema::table('automations', function (Blueprint $table): void {
            $table->dropForeign(['rental_property_id']);
            $table->dropColumn('rental_property_id');
        });

        Schema::table('domotics_events', function (Blueprint $table): void {
            $table->dropForeign(['rental_property_id']);
            $table->dropColumn('rental_property_id');
        });
    }

    private function backfillRentalPropertyIds(): void
    {
        $tables = ['devices', 'access_points', 'access_grants', 'automations', 'domotics_events'];

        foreach ($tables as $table) {
            DB::statement("
                UPDATE {$table} AS d
                SET rental_property_id = (
                    SELECT rp.id
                    FROM rental_properties AS rp
                    WHERE rp.property_id = d.property_id
                    LIMIT 1
                )
                WHERE d.property_id IS NOT NULL
            ");
        }
    }
};
