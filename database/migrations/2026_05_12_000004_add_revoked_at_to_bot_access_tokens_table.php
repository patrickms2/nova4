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
            if (! $this->schema()->hasColumn('bot_access_tokens', 'revoked_at')) {
                $table->timestamp('revoked_at')->nullable()->after('expires_at')->index();
            }
        });
    }

    public function down(): void
    {
        if (! $this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            if ($this->schema()->hasColumn('bot_access_tokens', 'revoked_at')) {
                $table->dropColumn('revoked_at');
            }
        });
    }
};
