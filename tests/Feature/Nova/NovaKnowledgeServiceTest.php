<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Models\NovaAiKnowledge;
use App\Models\NovaBusiness;
use App\Services\Nova\NovaKnowledgeService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Embeddings;
use Tests\TestCase;

final class NovaKnowledgeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('nova_ai.embeddings.enabled', true);
        config()->set('nova_ai.embeddings.provider', 'openai');
        config()->set('nova_ai.embeddings.model', 'text-embedding-3-small');
        config()->set('ai.providers.openai.key', 'test-key');
    }

    private function fakeTopicEmbeddings(): void
    {
        // Deterministic 2D vectors keyed by topic so cosine similarity is predictable.
        Embeddings::fake(function ($prompt): array {
            $text = mb_strtolower($prompt->inputs[0]);

            $vector = match (true) {
                str_contains($text, 'vino'), str_contains($text, 'bodega') => [1.0, 0.0],
                str_contains($text, 'taxi'), str_contains($text, 'traslado') => [0.0, 1.0],
                default => [0.5, 0.5],
            };

            return [$vector];
        });
    }

    private function makeBusiness(): NovaBusiness
    {
        return NovaBusiness::create([
            'name' => 'La Geria',
            'slug' => 'la-geria-'.uniqid(),
        ]);
    }

    public function test_observer_stores_embedding_vector_on_save(): void
    {
        $this->fakeTopicEmbeddings();

        $business = $this->makeBusiness();

        $knowledge = NovaAiKnowledge::create([
            'nova_business_id' => $business->id,
            'title' => 'Vinos',
            'content' => 'Tenemos vino tinto y blanco de la bodega.',
            'status' => 'active',
        ]);

        $this->assertEquals([1.0, 0.0], $knowledge->fresh()->embedding);
        $this->assertNotNull($knowledge->fresh()->vectorized_at);
    }

    public function test_relevant_knowledge_ranks_by_semantic_similarity(): void
    {
        $this->fakeTopicEmbeddings();

        $business = $this->makeBusiness();

        NovaAiKnowledge::create([
            'nova_business_id' => $business->id,
            'title' => 'Taxi',
            'content' => 'Servicio de taxi y traslado al aeropuerto.',
            'status' => 'active',
        ]);

        NovaAiKnowledge::create([
            'nova_business_id' => $business->id,
            'title' => 'Vinos',
            'content' => 'Cata de vino tinto en la bodega.',
            'status' => 'active',
        ]);

        $results = app(NovaKnowledgeService::class)
            ->relevantKnowledge($business, 'Quiero comprar vino', 5);

        $this->assertNotEmpty($results);
        $this->assertSame('Vinos', $results[0]['title']);
    }

    public function test_falls_back_to_keyword_search_when_embeddings_disabled(): void
    {
        config()->set('nova_ai.embeddings.enabled', false);

        $business = $this->makeBusiness();

        NovaAiKnowledge::create([
            'nova_business_id' => $business->id,
            'title' => 'Taxi',
            'content' => 'Servicio de taxi y traslado al aeropuerto.',
            'status' => 'active',
        ]);

        NovaAiKnowledge::create([
            'nova_business_id' => $business->id,
            'title' => 'Vinos',
            'content' => 'Cata de vino tinto en la bodega de La Geria.',
            'status' => 'active',
        ]);

        $results = app(NovaKnowledgeService::class)
            ->relevantKnowledge($business, 'informacion sobre bodega', 5);

        $this->assertNotEmpty($results);
        $this->assertSame('Vinos', $results[0]['title']);
    }
}
