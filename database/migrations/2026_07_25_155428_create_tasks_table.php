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
        Schema::table('tasks', function (Blueprint $table) {
            // Añadir columnas si no existen
            if (!Schema::hasColumn('tasks', 'title')) {
                $table->string('title')->after('id');
            }
            if (!Schema::hasColumn('tasks', 'description')) {
                $table->text('description')->nullable()->after('title');
            }
            if (!Schema::hasColumn('tasks', 'status')) {
                $table->string('status')->default('pending')->after('description');
            }
            if (!Schema::hasColumn('tasks', 'priority')) {
                $table->string('priority')->default('medium')->after('status');
            }
            if (!Schema::hasColumn('tasks', 'due_date')) {
                $table->date('due_date')->nullable()->after('priority');
            }
            if (!Schema::hasColumn('tasks', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete()->after('due_date');
            }
            if (!Schema::hasColumn('tasks', 'project_id')) {
                $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete()->after('assigned_to');
            }
            if (!Schema::hasColumn('tasks', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('project_id');
            }
            if (!Schema::hasColumn('tasks', 'is_completed')) {
                $table->boolean('is_completed')->default(false)->after('sort_order');
            }
            if (!Schema::hasColumn('tasks', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('is_completed');
            }
            if (!Schema::hasColumn('tasks', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
