<?php

namespace App\Observers;

use App\Models\NovaAiKnowledge;
use App\Services\Nova\NovaKnowledgeEmbedder;

class NovaAiKnowledgeObserver
{
    public function __construct(private readonly NovaKnowledgeEmbedder $embedder) {}

    /**
     * Generate the embedding vector before the fragment is persisted.
     *
     * Runs only when the content changed or no embedding exists yet, so simple
     * status/metadata edits do not trigger an unnecessary embeddings request.
     */
    public function saving(NovaAiKnowledge $knowledge): void
    {
        if (! $this->embedder->enabled()) {
            return;
        }

        $needsEmbedding = $knowledge->isDirty('content') || blank($knowledge->embedding);

        if (! $needsEmbedding || blank($knowledge->content)) {
            return;
        }

        $vector = $this->embedder->embed((string) $knowledge->content);

        if ($vector === null) {
            return;
        }

        $knowledge->embedding = $vector;
        $knowledge->vectorized_at = now();
    }
}
