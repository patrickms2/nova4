<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_pending_interactions')) {
            return;
        }

        $schema->table('bot_pending_interactions', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('bot_pending_interactions', 'draft_value')) {
                $table->json('draft_value')->nullable();
            }

            if (! $schema->hasColumn('bot_pending_interactions', 'draft_current_step')) {
                $table->unsignedInteger('draft_current_step')->nullable();
            }

            if (! $schema->hasColumn('bot_pending_interactions', 'draft_schema_hash')) {
                $table->string('draft_schema_hash', 64)->nullable()->index();
            }

            if (! $schema->hasColumn('bot_pending_interactions', 'draft_saved_at')) {
                $table->timestamp('draft_saved_at')->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('bot_pending_interactions')) {
            return;
        }

        $schema->table('bot_pending_interactions', function (Blueprint $table) use ($schema): void {
            foreach ([
                'draft_saved_at',
                'draft_schema_hash',
                'draft_current_step',
                'draft_value',
            ] as $column) {
                if ($schema->hasColumn('bot_pending_interactions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
