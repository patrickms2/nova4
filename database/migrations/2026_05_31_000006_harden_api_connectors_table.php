<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $this->ensureTablesExist(['api_connectors'], 'harden api_connectors');

        $schema->table('api_connectors', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('api_connectors', 'base_url')) {
                $table->text('base_url')->change();
            }

            if (! $schema->hasColumn('api_connectors', 'bot_id')) {
                $table->foreignId('bot_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('agentic_bots')
                    ->nullOnDelete();
            }

            if (! $schema->hasColumn('api_connectors', 'owner_type')) {
                $table->string('owner_type')->nullable()->after('bot_id');
            }

            if (! $schema->hasColumn('api_connectors', 'owner_id')) {
                $table->string('owner_id', 128)->nullable()->after('owner_type');
            }

            if (! $schema->hasColumn('api_connectors', 'allowed_methods')) {
                $table->json('allowed_methods')->nullable()->after('default_headers');
            }

            if (! $schema->hasColumn('api_connectors', 'allowed_path_patterns')) {
                $table->json('allowed_path_patterns')->nullable()->after('allowed_methods');
            }

            if (! $schema->hasColumn('api_connectors', 'last_tested_at')) {
                $table->timestamp('last_tested_at')->nullable()->after('is_active');
            }

            if (! $schema->hasColumn('api_connectors', 'last_test_status')) {
                $table->integer('last_test_status')->nullable()->after('last_tested_at');
            }

            if (! $schema->hasColumn('api_connectors', 'last_test_latency_ms')) {
                $table->unsignedInteger('last_test_latency_ms')->nullable()->after('last_test_status');
            }

            if (! $schema->hasColumn('api_connectors', 'last_test_error')) {
                $table->text('last_test_error')->nullable()->after('last_test_latency_ms');
            }
        });

        $schema->table('api_connectors', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('api_connectors', 'bot_id')) {
                $table->index(['bot_id', 'is_active'], 'api_connectors_bot_active_index');
            }

            if ($schema->hasColumn('api_connectors', 'owner_type') && $schema->hasColumn('api_connectors', 'owner_id')) {
                $table->index(['owner_type', 'owner_id'], 'api_connectors_owner_index');
            }
        });
    }

    public function down(): void
    {
        $schema = $this->schema();

        if (! $schema->hasTable('api_connectors')) {
            return;
        }

        $schema->table('api_connectors', function (Blueprint $table) use ($schema): void {
            foreach ([
                'api_connectors_bot_active_index',
                'api_connectors_owner_index',
            ] as $index) {
                try {
                    $table->dropIndex($index);
                } catch (Throwable) {
                    //
                }
            }

            $dropColumns = [];

            foreach ([
                'bot_id',
                'owner_type',
                'owner_id',
                'allowed_methods',
                'allowed_path_patterns',
                'last_tested_at',
                'last_test_status',
                'last_test_latency_ms',
                'last_test_error',
            ] as $column) {
                if ($schema->hasColumn('api_connectors', $column)) {
                    $dropColumns[] = $column;
                }
            }

            if ($dropColumns !== []) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
