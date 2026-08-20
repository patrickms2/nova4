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
        if (! Schema::hasColumn('gastos', 'type')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->string('type')->default('expense')->index()->after('categoria');
            });
        }

        if (! Schema::hasColumn('gastos', 'category_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->foreignId('category_id')->nullable()->after('type')->constrained('categories')->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('gastos', 'user_id')) {
            Schema::table('gastos', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('id')->constrained('users')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['user_id']);
            $table->dropColumn(['type', 'category_id', 'user_id']);
        });
    }
};
