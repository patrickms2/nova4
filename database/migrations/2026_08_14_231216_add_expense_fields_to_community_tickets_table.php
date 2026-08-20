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
        Schema::table('community_tickets', function (Blueprint $table): void {
            $table->string('type')->default('general')->after('description')->index();
            $table->decimal('amount', 12, 2)->nullable()->after('type');
            $table->string('attachment_path')->nullable()->after('amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('community_tickets', function (Blueprint $table): void {
            $table->dropColumn(['type', 'amount', 'attachment_path']);
        });
    }
};
