<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use App\Ai\Agents\NovaBookingExtractionAgent;
use App\Ai\Agents\NovaIntentAgent;
use App\Ai\Agents\NovaResponseAgent;
use App\Services\Nova\NovaAiService;
use App\Services\NovaPromptLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\TestCase;

final class NovaAiServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        NovaPromptLoader::clearCache();

        // Ollama needs no API key, so the service is "enabled" without secrets.
        config()->set('nova_ai.provider', 'ollama');
        config()->set('nova_ai.model', 'qwen3:8b');
        config()->set('nova_ai.response_model', 'qwen3:8b');
        config()->set('nova_ai.failover', []);
    }

    public function test_detect_intent_maps_agent_json_response(): void
    {
        NovaIntentAgent::fake([
            '{"intent":"taxi_booking","confidence":0.92,"reasoning":"asks for a taxi"}',
        ]);

        $result = (new NovaAiService)->detectIntent('Necesito un taxi al aeropuerto');

        $this->assertSame('taxi_booking', $result['intent']);
        $this->assertSame(0.92, $result['confidence']);
        $this->assertSame('asks for a taxi', $result['reasoning']);

        NovaIntentAgent::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('taxi'));
    }

    public function test_detect_intent_tolerates_markdown_code_fences(): void
    {
        NovaIntentAgent::fake([
            "```json\n{\"intent\":\"winery_visit\",\"confidence\":0.7}\n```",
        ]);

        $result = (new NovaAiService)->detectIntent('Quiero visitar una bodega');

        $this->assertSame('winery_visit', $result['intent']);
        $this->assertSame(0.7, $result['confidence']);
    }

    public function test_extract_booking_data_maps_nested_objects(): void
    {
        NovaBookingExtractionAgent::fake([
            json_encode([
                'date' => ['label' => 'mañana', 'value' => '2026-06-18'],
                'time' => ['label' => '20:00', 'value' => '20:00'],
                'party_size' => 4,
                'customer_name' => 'Ana',
                'customer_phone' => '666123456',
                'preferences' => null,
                'origin' => 'Arrecife',
                'destination' => 'La Geria',
            ]),
        ]);

        $result = (new NovaAiService)->extractBookingData('Mesa para 4 mañana a las 20:00');

        $this->assertSame(['label' => 'mañana', 'value' => '2026-06-18'], $result['date']);
        $this->assertSame(4, $result['party_size']);
        $this->assertSame('Ana', $result['customer_name']);
        $this->assertSame('La Geria', $result['destination']);
    }

    public function test_generate_response_returns_agent_text(): void
    {
        NovaResponseAgent::fake(['¡Hola! ¿En qué puedo ayudarte hoy?']);

        $response = (new NovaAiService)->generateResponse(
            'Hola',
            [['role' => 'user', 'content' => 'Hola']],
        );

        $this->assertSame('¡Hola! ¿En qué puedo ayudarte hoy?', $response);
    }

    public function test_falls_back_to_keyword_detection_when_provider_disabled(): void
    {
        // No API key for OpenAI -> service disabled -> keyword fallback (no agent call).
        config()->set('nova_ai.provider', 'openai');
        config()->set('ai.providers.openai.key', null);

        $result = (new NovaAiService)->detectIntent('Quiero una mesa para cenar');

        $this->assertSame('restaurant_booking', $result['intent']);
        $this->assertSame(0.7, $result['confidence']);
    }
}
