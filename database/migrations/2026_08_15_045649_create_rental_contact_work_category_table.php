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
        Schema::create('rental_contact_work_category', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rental_contact_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_category_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['rental_contact_id', 'work_category_id'], 'rental_contact_work_category_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rental_contact_work_category');
    }
};
