<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('agentic_bots', 'embedding_provider_api_key')) {
                $table->text('embedding_provider_api_key')->nullable()->after('chat_provider_api_key');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('agentic_bots', 'embedding_provider_api_key')) {
                $table->dropColumn('embedding_provider_api_key');
            }
        });
    }
};
