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
        Schema::table('projects', function (Blueprint $table) {
            // Jerarquía
            if (!Schema::hasColumn('projects', 'parent_id')) {
                $table->foreignId('parent_id')->nullable()->constrained('projects')->nullOnDelete()->after('id');
            }
            if (!Schema::hasColumn('projects', 'project_category_id')) {
                $table->foreignId('project_category_id')->nullable()->after('color');
            }

            // Fechas
            if (!Schema::hasColumn('projects', 'start_date')) {
                $table->date('start_date')->nullable()->after('description');
            }
            if (!Schema::hasColumn('projects', 'end_date')) {
                $table->date('end_date')->nullable()->after('start_date');
            }

            // Fases y metadatos
            if (!Schema::hasColumn('projects', 'phase')) {
                $table->string('phase')->default('planning')->after('end_date'); // planning, development, testing, deployment, completed
            }
            if (!Schema::hasColumn('projects', 'icon')) {
                $table->string('icon')->nullable()->after('color');
            }
            if (!Schema::hasColumn('projects', 'is_public')) {
                $table->boolean('is_public')->default(false)->after('status');
            }
            if (!Schema::hasColumn('projects', 'sort_order')) {
                $table->integer('sort_order')->default(0)->after('is_public');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
