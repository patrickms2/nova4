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
            ->where('scope_type', 'actor')
            ->whereNotNull('bot_id')
            ->where('scope_id', 'not like', 'bot:%')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $targetScopeId = mb_substr('bot:'.$row->bot_id.':'.$row->scope_id, 0, 160);
                    $duplicate = $this->database()
                        ->table('workflow_memories')
                        ->where('scope_type', 'actor')
                        ->where('scope_id', $targetScopeId)
                        ->where('namespace', $row->namespace)
                        ->where('key', $row->key)
                        ->exists();

                    if ($duplicate) {
                        $this->database()
                            ->table('workflow_memories')
                            ->where('id', $row->id)
                            ->delete();

                        continue;
                    }

                    $this->database()
                        ->table('workflow_memories')
                        ->where('id', $row->id)
                        ->update(['scope_id' => $targetScopeId]);
                }
            });
    }

    public function down(): void
    {
        //
    }
};
