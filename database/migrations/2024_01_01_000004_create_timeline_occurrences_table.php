<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('timeline.table_prefix') . 'occurrences', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained(config('timeline.table_prefix') . 'events')->cascadeOnDelete();
            $table->foreignId('rule_id')->nullable()->constrained(config('timeline.table_prefix') . 'rules')->nullOnDelete();
            $table->foreignId('location_id')->nullable()->constrained(config('timeline.table_prefix') . 'locations')->nullOnDelete();
            $table->dateTime('starts_at');
            $table->dateTime('ends_at')->nullable();
            $table->string('status')->default('scheduled');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index('starts_at');
            $table->index(['event_id', 'starts_at']);
            $table->index(['location_id', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });
    }
};
