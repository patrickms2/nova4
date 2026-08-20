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
        Schema::create('work_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('work_session_id')->unique()->constrained('work_sessions')->cascadeOnDelete();
            $table->string('voice_path')->nullable();
            $table->text('voice_transcription')->nullable();
            $table->text('summary')->nullable();
            $table->json('photos')->nullable();
            $table->json('ai_metadata')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('work_reports');
    }
};
