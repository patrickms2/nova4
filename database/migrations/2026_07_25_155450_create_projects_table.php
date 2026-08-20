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
            // Añadir columnas si no existen
            if (!Schema::hasColumn('projects', 'name')) {
                $table->string('name')->after('id');
            }
            if (!Schema::hasColumn('projects', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('projects', 'color')) {
                $table->string('color')->default('#3b82f6')->after('description');
            }
            if (!Schema::hasColumn('projects', 'status')) {
                $table->string('status')->default('active')->after('color');
            }
            if (!Schema::hasColumn('projects', 'created_by')) {
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete()->after('status');
            }
            if (!Schema::hasColumn('projects', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
