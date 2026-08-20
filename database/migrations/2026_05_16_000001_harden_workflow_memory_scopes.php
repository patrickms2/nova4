<?php

use Heiner\FilamentAgenticChatbot\Support\PackageMigration;

return new class extends PackageMigration
{
    public function up(): void
    {
        if (! $this->schema()->hasTable('workflow_memories')) {
            return;
        }

        $this->database()
            ->table('workflow_memories')
            ->whereIn('scope_type', ['bot', 'actor'])
            ->update([
                'bot_conversation_id' => null,
                'workflow_run_id' => null,
            ]);
    }

    public function down(): void
    {
        //
    }
};
