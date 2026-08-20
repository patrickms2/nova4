<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        $this->ensureTablesExist(['agentic_bots'], 'add assistant profile to bots');

        if ($this->schema()->hasColumn('agentic_bots', 'assistant_profile')) {
            return;
        }

        $this->schema()->table('agentic_bots', function (Blueprint $table): void {
            $table->json('assistant_profile')->nullable()->after('system_prompt');
        });
    }

    public function down(): void
    {
        if (! $this->schema()->hasColumn('agentic_bots', 'assistant_profile')) {
            return;
        }

        $this->schema()->table('agentic_bots', function (Blueprint $table): void {
            $table->dropColumn('assistant_profile');
        });
    }
};
