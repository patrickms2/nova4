<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('timeline.table_prefix') . 'exceptions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('occurrence_id')->constrained(config('timeline.table_prefix') . 'occurrences')->cascadeOnDelete();
            $table->string('action');
            $table->dateTime('starts_at')->nullable();
            $table->dateTime('ends_at')->nullable();
            $table->foreignId('location_id')->nullable()->constrained(config('timeline.table_prefix') . 'locations')->nullOnDelete();
            $table->json('payload')->nullable();
            $table->timestamps();
        });
    }
};
