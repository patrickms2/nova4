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
            if (! $this->schema()->hasColumn('bot_access_tokens', 'max_input_tokens')) {
                $table->unsignedBigInteger('max_input_tokens')->nullable()->after('rate_limit_per_minute');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'max_output_tokens')) {
                $table->unsignedBigInteger('max_output_tokens')->nullable()->after('max_input_tokens');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'monthly_token_budget')) {
                $table->unsignedBigInteger('monthly_token_budget')->nullable()->after('max_output_tokens');
            }

            if (! $this->schema()->hasColumn('bot_access_tokens', 'monthly_cost_budget_cents')) {
                $table->unsignedBigInteger('monthly_cost_budget_cents')->nullable()->after('monthly_token_budget');
            }
        });
    }

    public function down(): void
    {
        if (! $this->schema()->hasTable('bot_access_tokens')) {
            return;
        }

        $this->schema()->table('bot_access_tokens', function (Blueprint $table): void {
            foreach (['monthly_cost_budget_cents', 'monthly_token_budget', 'max_output_tokens', 'max_input_tokens'] as $column) {
                if ($this->schema()->hasColumn('bot_access_tokens', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
