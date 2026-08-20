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
        Schema::table('servers', function (Blueprint $table) {
            $table->foreignId('nova_business_id')->nullable()->after('id')
                ->constrained('nova_businesses')->nullOnDelete();
            $table->string('type')->nullable()->after('nova_business_id');
            $table->string('auth_type')->nullable()->after('transport');
            $table->json('credentials')->nullable()->after('auth_type');
            $table->string('status')->default('active')->after('is_active');
            $table->timestamp('last_checked_at')->nullable()->after('status');
            $table->text('last_error')->nullable()->after('last_checked_at');

            $table->index(['nova_business_id', 'is_active']);
        });

        if (Schema::hasTable('nova_requests')) {
            Schema::table('nova_requests', function (Blueprint $table) {
                $table->foreignId('nova_business_id')->nullable()->after('id')
                    ->constrained('nova_businesses')->nullOnDelete();
                $table->index('nova_business_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('servers', function (Blueprint $table) {
            $table->dropForeign(['nova_business_id']);
            $table->dropIndex(['nova_business_id', 'is_active']);
            $table->dropColumn(['nova_business_id', 'type', 'auth_type', 'credentials', 'status', 'last_checked_at', 'last_error']);
        });

        if (Schema::hasTable('nova_requests') && Schema::hasColumn('nova_requests', 'nova_business_id')) {
            Schema::table('nova_requests', function (Blueprint $table) {
                $table->dropForeign(['nova_business_id']);
                $table->dropColumn('nova_business_id');
            });
        }
    }
};
