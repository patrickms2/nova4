<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_plan_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_plan_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week');
            $table->timestamps();

            $table->unique(['community_plan_item_id', 'day_of_week']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_plan_days');
    }
};
