<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('community_plan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('community_plan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('work_catalog_id')->nullable()->constrained('work_catalog')->nullOnDelete();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('requirements')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('community_plan_items');
    }
};
