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
        Schema::create('access_grant_access_point', function (Blueprint $table) {
            $table->id();
            $table->foreignId('access_grant_id')->constrained('access_grants')->cascadeOnDelete();
            $table->foreignId('access_point_id')->constrained('access_points')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['access_grant_id', 'access_point_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_grant_access_point');
    }
};
