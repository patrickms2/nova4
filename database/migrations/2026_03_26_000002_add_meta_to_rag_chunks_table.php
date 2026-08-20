<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_knowledge_chunks') || $schema->hasColumn('bot_knowledge_chunks', 'meta')) {
            return;
        }

        $schema->table('bot_knowledge_chunks', function (Blueprint $table): void {
            $table->json('meta')->nullable()->after('token_count');
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_knowledge_chunks') || ! $schema->hasColumn('bot_knowledge_chunks', 'meta')) {
            return;
        }

        $schema->table('bot_knowledge_chunks', function (Blueprint $table): void {
            $table->dropColumn('meta');
        });
    }
};
