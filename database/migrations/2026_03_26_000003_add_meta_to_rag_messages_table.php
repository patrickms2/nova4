<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_messages') || $schema->hasColumn('bot_messages', 'meta')) {
            return;
        }

        $schema->table('bot_messages', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('sources');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_messages') || ! $schema->hasColumn('bot_messages', 'meta')) {
            return;
        }

        $schema->table('bot_messages', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
