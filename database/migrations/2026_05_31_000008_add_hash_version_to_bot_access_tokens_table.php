<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $this->ensureTablesExist(['bot_access_tokens'], 'add bot access token hash version');

        if ($schema->hasColumn('bot_access_tokens', 'token_hash_version')) {
            return;
        }

        $schema->table('bot_access_tokens', function (Blueprint $table): void {
            $table->unsignedTinyInteger('token_hash_version')
                ->default(1)
                ->after('token_hash');
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_access_tokens') || ! $schema->hasColumn('bot_access_tokens', 'token_hash_version')) {
            return;
        }

        $schema->table('bot_access_tokens', function (Blueprint $table): void {
            $table->dropColumn('token_hash_version');
        });
    }
};
