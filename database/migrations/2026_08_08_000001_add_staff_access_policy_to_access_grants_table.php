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
        Schema::table('access_grants', function (Blueprint $table): void {
            $table->string('role')->nullable()->after('name');
            $table->json('allowed_weekdays')->nullable()->after('valid_until');
            $table->time('allowed_time_from')->nullable()->after('allowed_weekdays');
            $table->time('allowed_time_until')->nullable()->after('allowed_time_from');
            $table->boolean('report_required')->default(false)->after('is_active');
            $table->boolean('voice_required')->default(false)->after('report_required');
            $table->boolean('photo_required')->default(false)->after('voice_required');
            $table->unsignedTinyInteger('minimum_photos')->default(0)->after('photo_required');
            $table->timestamp('revoked_at')->nullable()->after('minimum_photos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('access_grants', function (Blueprint $table): void {
            $table->dropColumn([
                'role',
                'allowed_weekdays',
                'allowed_time_from',
                'allowed_time_until',
                'report_required',
                'voice_required',
                'photo_required',
                'minimum_photos',
                'revoked_at',
            ]);
        });
    }
};
