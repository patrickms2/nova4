<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

return new class extends PackageMigration
{
    public function up(): void
    {
        $schema = $this->schema();

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if (! $schema->hasColumn('agentic_bots', 'public_id')) {
                $table->string('public_id', 36)->nullable()->after('id');
            }

            if (! $schema->hasColumn('agentic_bots', 'allowed_domains')) {
                $table->json('allowed_domains')->nullable()->after('runtime_config');
            }

            if (! $schema->hasColumn('agentic_bots', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('allowed_domains');
            }
        });

        $this->database()->table('agentic_bots')
            ->select('id')
            ->whereNull('public_id')
            ->orderBy('id')
            ->chunkById(200, function ($bots): void {
                foreach ($bots as $bot) {
                    $this->database()->table('agentic_bots')
                        ->where('id', $bot->id)
                        ->update(['public_id' => (string) Str::uuid()]);
                }
            });

        $this->database()->table('agentic_bots')
            ->whereNull('is_active')
            ->update(['is_active' => true]);

        if (! $schema->hasIndex('agentic_bots', 'agentic_bots_public_id_unique')) {
            $schema->table('agentic_bots', function (Blueprint $table): void {
                $table->unique('public_id', 'agentic_bots_public_id_unique');
            });
        }

        if (! $schema->hasIndex('agentic_bots', 'agentic_bots_is_active_index')) {
            $schema->table('agentic_bots', function (Blueprint $table): void {
                $table->index('is_active', 'agentic_bots_is_active_index');
            });
        }
    }

    public function down(): void
    {
        $schema = $this->schema();

        $schema->table('agentic_bots', function (Blueprint $table) use ($schema): void {
            if ($schema->hasColumn('agentic_bots', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if ($schema->hasColumn('agentic_bots', 'allowed_domains')) {
                $table->dropColumn('allowed_domains');
            }

            if ($schema->hasColumn('agentic_bots', 'public_id')) {
                $table->dropColumn('public_id');
            }
        });
    }
};
