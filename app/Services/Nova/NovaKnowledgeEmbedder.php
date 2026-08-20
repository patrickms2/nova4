<?php

declare(strict_types=1);

namespace App\Services\Nova;

use Illuminate\Support\Facades\Log;
use Laravel\Ai\Embeddings;

/**
 * Generates embedding vectors for Nova knowledge fragments using laravel/ai.
 *
 * The embeddings provider is configured independently from the chat provider
 * (see config/nova_ai.php) because Ollama does not support embeddings. When
 * embeddings are disabled or the configured provider has no API key, the
 * embedder reports itself as disabled so callers can fall back gracefully.
 */
final class NovaKnowledgeEmbedder
{
    public function enabled(): bool
    {
        if (! config('nova_ai.embeddings.enabled', true)) {
            return false;
        }

        $provider = (string) config('nova_ai.embeddings.provider', 'openai');

        return filled(config("ai.providers.{$provider}.key"));
    }

    /**
     * Generate an embedding vector for the given text.
     *
     * @return array<int, float>|null
     */
    public function embed(string $text, bool $cache = false): ?array
    {
        $text = trim($text);

        if ($text === '' || ! $this->enabled()) {
            return null;
        }

        try {
            $pending = Embeddings::for([$text]);

            if ($cache) {
                $pending = $pending->cache();
            }

            $response = $pending->generate(
                provider: (string) config('nova_ai.embeddings.provider', 'openai'),
                model: (string) config('nova_ai.embeddings.model', 'text-embedding-3-small'),
            );

            $vector = $response->first();

            return $vector === [] ? null : $vector;
        } catch (\Throwable $exception) {
            Log::error('Nova knowledge embedding failed', [
                'error' => $exception->getMessage(),
            ]);

            return null;
        }
    }
}
