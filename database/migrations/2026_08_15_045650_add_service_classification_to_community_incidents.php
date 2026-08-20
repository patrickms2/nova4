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
        Schema::table('incidents', function (Blueprint $table): void {
            $table->foreignId('work_category_id')->nullable()->after('work_order_task_id')->constrained()->nullOnDelete();
            $table->foreignId('work_catalog_id')->nullable()->after('work_category_id')->constrained('work_catalog')->nullOnDelete();
        });

        Schema::table('community_tickets', function (Blueprint $table): void {
            $table->foreignId('work_category_id')->nullable()->after('community_department_id')->constrained()->nullOnDelete();
            $table->foreignId('work_catalog_id')->nullable()->after('work_category_id')->constrained('work_catalog')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_tickets', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('work_catalog_id');
            $table->dropConstrainedForeignId('work_category_id');
        });

        Schema::table('incidents', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('work_catalog_id');
            $table->dropConstrainedForeignId('work_category_id');
        });
    }
};
