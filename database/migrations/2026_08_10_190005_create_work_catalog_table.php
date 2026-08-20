<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_catalog', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_category_id')->constrained()->cascadeOnDelete();
            $table->string('code')->nullable()->unique();
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('requirements')->nullable();
            $table->string('default_priority')->default('normal');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_catalog');
    }
};
