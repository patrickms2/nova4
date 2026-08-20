<?php

declare(strict_types=1);

namespace Tests\Feature\Nova;

use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class CopilotWebhookTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_copilot_endpoint_returns_greeting_and_workspace_menu(): void
    {
        $response = $this->postJson(route('api.nova.whatsapp.copilot'), [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'from' => '34600000000',
                                        'text' => ['body' => 'Hola'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], ['auto_reply' => false]);

        $response->assertOk();
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('input.type', 'text');
        $response->assertJsonPath('intent.name', 'greeting');
        $response->assertJsonPath('intent.confidence', 'high');
        $this->assertStringContainsString('Soy el Copiloto', $response->json('response.text'));
        $this->assertNotEmpty($response->json('response.menu'));
    }

    public function test_copilot_endpoint_understands_create_expense_intent(): void
    {
        $response = $this->postJson(route('api.nova.whatsapp.copilot'), [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'from' => '34600000000',
                                        'text' => ['body' => 'He comprado pintura por 42€'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], ['auto_reply' => false]);

        $response->assertOk();
        $response->assertJsonPath('intent.name', 'create');
        $response->assertJsonPath('intent.target_capability', 'expenses');
        $this->assertNotNull($response->json('context.active_conversation'));
        $this->assertStringContainsString('gastos', $response->json('response.text'));
    }

    public function test_copilot_endpoint_responds_to_power_menu_trigger(): void
    {
        $response = $this->postJson(route('api.nova.whatsapp.copilot'), [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'from' => '34600000000',
                                        'text' => ['body' => 'menu'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], ['auto_reply' => false]);

        $response->assertOk();
        $response->assertJsonPath('intent.name', 'menu');
        $this->assertNotEmpty($response->json('response.menu'));
    }

    public function test_copilot_endpoint_extracts_phone_from_payload(): void
    {
        $response = $this->postJson(route('api.nova.whatsapp.copilot'), [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'from' => '34611111111',
                                        'text' => ['body' => 'Hola'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ], ['auto_reply' => false]);

        $response->assertOk();
        $this->assertStringContainsString('34611111111', $response->json('context.phone') ?? '');
    }
}
