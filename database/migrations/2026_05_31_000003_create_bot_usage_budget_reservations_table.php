<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;
use Illuminate\Database\Schema\Blueprint;

return new class extends PackageMigration
{
    public function up(): void
    {
        if ($this->schema()->hasTable('bot_usage_budget_reservations')) {
            return;
        }

        $this->ensureTablesExist(
            ['agentic_bots', 'bot_access_tokens', 'bot_usage_events'],
            'create bot usage budget reservations',
        );

        $this->schema()->create('bot_usage_budget_reservations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('bot_id')->constrained('agentic_bots')->cascadeOnDelete();
            $table->foreignId('bot_access_token_id')->nullable()->constrained('bot_access_tokens')->nullOnDelete();
            $table->foreignId('bot_usage_event_id')->nullable()->constrained('bot_usage_events')->nullOnDelete();
            $table->string('status', 16)->default('reserved');
            $table->unsignedBigInteger('reserved_tokens')->default(0);
            $table->decimal('reserved_cost_cents', 12, 4)->nullable();
            $table->unsignedBigInteger('actual_tokens')->nullable();
            $table->decimal('actual_cost_cents', 12, 4)->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['bot_id', 'status', 'expires_at'], 'budget_reservations_bot_status_expiry');
            $table->index(['bot_access_token_id', 'status', 'expires_at'], 'budget_reservations_token_status_expiry');
        });
    }

    public function down(): void
    {
        $this->schema()->dropIfExists('bot_usage_budget_reservations');
    }
};
