<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversations') || $schema->hasColumn('bot_conversations', 'meta')) {
            return;
        }

        $schema->table('bot_conversations', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('context_area');
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_conversations') || ! $schema->hasColumn('bot_conversations', 'meta')) {
            return;
        }

        $schema->table('bot_conversations', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
