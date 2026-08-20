<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if (! $this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            if (! $this->schema()->hasColumn('bot_access_tokens', 'owner_type')) {
                $table->string('owner_type')->nullable()->after('bot_id');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'owner_id')) {
                $table->string('owner_id', 128)->nullable()->after('owner_type');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'created_by_type')) {
                $table->string('created_by_type')->nullable()->after('owner_id');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'created_by_id')) {
                $table->string('created_by_id', 128)->nullable()->after('created_by_type');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'channel')) {
                $table->string('channel', 64)->nullable()->after('name');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'channel_label')) {
                $table->string('channel_label')->nullable()->after('channel');
            }
        });

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            if (! $this->schema()->hasIndex('bot_access_tokens', 'bot_access_tokens_owner_index')) {
                $table->index(['owner_type', 'owner_id'], 'bot_access_tokens_owner_index');
            }

            if (! $this->schema()->hasIndex('bot_access_tokens', 'bot_access_tokens_created_by_index')) {
                $table->index(['created_by_type', 'created_by_id'], 'bot_access_tokens_created_by_index');
            }

            if (! $this->schema()->hasIndex('bot_access_tokens', 'bot_access_tokens_channel_index')) {
                $table->index('channel', 'bot_access_tokens_channel_index');
            }

            if (! $this->schema()->hasIndex('bot_access_tokens', 'bot_access_tokens_bot_channel_index')) {
                $table->index(['bot_id', 'channel'], 'bot_access_tokens_bot_channel_index');
            }
        });
    }

    public function down(): void
    {
        if (! $this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            foreach ([
                'bot_access_tokens_bot_channel_index',
                'bot_access_tokens_channel_index',
                'bot_access_tokens_created_by_index',
                'bot_access_tokens_owner_index',
            ] as $index) {
                if ($this->schema()->hasIndex('bot_access_tokens', $index)) {
                    $table->dropIndex($index);
                }
            }
        });

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            foreach ([
                'channel_label',
                'channel',
                'created_by_id',
                'created_by_type',
                'owner_id',
                'owner_type',
            ] as $column) {
                if ($this->schema()->hasColumn('bot_access_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
