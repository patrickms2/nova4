<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_id')->constrained()->cascadeOnDelete();
            $table->date('valid_from');
            $table->date('valid_until')->nullable();
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('community_plans', function (Blueprint $table) {
            $table->foreignId('replaced_by_id')->nullable()->constrained('community_plans')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('community_plans', function (Blueprint $table) {
            $table->dropForeign(['replaced_by_id']);
            $table->dropColumn('replaced_by_id');
        });

        Schema::dropIfExists('community_plans');
    }
};
