<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('community_attendance_community', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('community_attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['community_attendance_id', 'community_id'], 'attendance_community_unique');
        });

        DB::table('community_attendances')
            ->whereNotNull('community_id')
            ->orderBy('id')
            ->chunkById(500, function ($attendances): void {
                $now = now();
                DB::table('community_attendance_community')->insert(
                    $attendances->map(fn ($attendance): array => [
                        'community_attendance_id' => $attendance->id,
                        'community_id' => $attendance->community_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all(),
                );
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('community_attendance_community');
    }
};
