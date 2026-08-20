<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Nova AI Provider
    |--------------------------------------------------------------------------
    |
    | The laravel/ai provider used by Nova's agents (intent detection, booking
    | extraction and response generation). Must match a provider defined in
    | config/ai.php (e.g. "openai", "ollama", "anthropic", "groq", ...).
    |
    */

    'provider' => env('NOVA_AI_PROVIDER', 'openai'),

    /*
    |--------------------------------------------------------------------------
    | Models
    |--------------------------------------------------------------------------
    |
    | The model used for structured tasks (intent + booking extraction) and the
    | model used for free-form response generation. When switching providers
    | (e.g. to Ollama) set these to a model available on that provider.
    |
    */

    'model' => env('NOVA_AI_MODEL', 'gpt-4o-mini'),

    'response_model' => env('NOVA_AI_RESPONSE_MODEL', env('NOVA_AI_MODEL', 'gpt-4o-mini')),

    /*
    |--------------------------------------------------------------------------
    | Failover Providers
    |--------------------------------------------------------------------------
    |
    | Optional comma-separated list of provider names to fall back to if the
    | primary provider fails. Example: NOVA_AI_FAILOVER="openai,groq".
    |
    */

    'failover' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('NOVA_AI_FAILOVER', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Knowledge Embeddings
    |--------------------------------------------------------------------------
    |
    | Semantic search for the "Conocimiento IA" (NovaAiKnowledge) feature. The
    | chat/text provider may be Ollama, but Ollama does not support embeddings,
    | so the embeddings provider is configured independently here. Supported
    | embeddings providers: openai, gemini, azure, cohere, mistral, jina,
    | voyageai. When disabled (or the provider has no API key), the knowledge
    | search transparently falls back to keyword matching.
    |
    */

    'embeddings' => [
        'enabled' => (bool) env('NOVA_AI_EMBEDDINGS_ENABLED', true),
        'provider' => env('NOVA_AI_EMBEDDINGS_PROVIDER', 'openai'),
        'model' => env('NOVA_AI_EMBEDDINGS_MODEL', 'text-embedding-3-small'),
    ],

];
