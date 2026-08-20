<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_quality_scenarios')) {
            return;
        }

        if (! $schema->hasColumn('bot_quality_scenarios', 'source')) {
            $schema->table('bot_quality_scenarios', function (Blueprint $table): void {
                $table->string('source', 32)->default('manual');
            });
        }

        if (! $schema->hasColumn('bot_quality_scenarios', 'source_bot_message_id')) {
            $this->ensureTablesExist(['bot_messages'], 'add feedback source to bot quality scenarios');

            $schema->table('bot_quality_scenarios', function (Blueprint $table): void {
                $table->foreignId('source_bot_message_id')
                    ->nullable()
                    ->constrained('bot_messages')
                    ->nullOnDelete();
                $table->unique('source_bot_message_id', 'bot_quality_scenarios_source_message_unique');
            });
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_quality_scenarios')) {
            return;
        }

        $schema->table('bot_quality_scenarios', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('bot_quality_scenarios', 'source_bot_message_id')) {
                $table->dropUnique('bot_quality_scenarios_source_message_unique');
                $table->dropConstrainedForeignId('source_bot_message_id');
            }

            if ($schema->hasColumn('bot_quality_scenarios', 'source')) {
                $table->dropColumn('source');
            }
        });
    }
};
