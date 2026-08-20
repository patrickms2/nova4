<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('work_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('community_plan_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_type')->default('STANDARD');
            $table->string('title');
            $table->text('instructions')->nullable();
            $table->text('requirements')->nullable();
            $table->string('priority')->default('normal');
            $table->string('status')->default('pending');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->string('result')->nullable();
            $table->string('requester_name')->nullable();
            $table->string('requester_phone')->nullable();
            $table->string('reference')->nullable();
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_tasks');
    }
};
