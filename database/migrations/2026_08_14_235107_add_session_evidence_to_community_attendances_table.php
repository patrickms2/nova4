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
        Schema::table('community_attendances', function (Blueprint $table): void {
            $table->decimal('check_in_latitude', 10, 7)->nullable()->after('checked_in_at');
            $table->decimal('check_in_longitude', 10, 7)->nullable()->after('check_in_latitude');
            $table->unsignedInteger('check_in_accuracy')->nullable()->after('check_in_longitude');
            $table->decimal('check_out_latitude', 10, 7)->nullable()->after('checked_out_at');
            $table->decimal('check_out_longitude', 10, 7)->nullable()->after('check_out_latitude');
            $table->unsignedInteger('check_out_accuracy')->nullable()->after('check_out_longitude');
            $table->string('closing_audio_path')->nullable()->after('notes');
            $table->string('closing_audio_mime_type')->nullable()->after('closing_audio_path');
            $table->string('transcription_status')->nullable()->after('closing_audio_mime_type');
            $table->text('transcription_error')->nullable()->after('transcription_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_attendances', function (Blueprint $table): void {
            $table->dropColumn([
                'check_in_latitude',
                'check_in_longitude',
                'check_in_accuracy',
                'check_out_latitude',
                'check_out_longitude',
                'check_out_accuracy',
                'closing_audio_path',
                'closing_audio_mime_type',
                'transcription_status',
                'transcription_error',
            ]);
        });
    }
};
