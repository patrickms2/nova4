<?php

declare(strict_types=1);

namespace Tests\Unit\Nova\Copilot;

use App\Domain\Nova\Copilot\ActionEngine;
use App\Domain\Nova\Copilot\ContextEngine;
use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\InputType;
use App\Domain\Nova\Copilot\Enums\IntentName;
use App\Domain\Nova\Copilot\InputAnalyzer;
use App\Domain\Nova\Copilot\IntentEngine;
use App\Domain\Nova\Copilot\MenuEngine;
use App\Domain\Nova\Copilot\ResponseBuilder;
use App\Domain\Nova\Copilot\ValueObjects\Action;
use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Domain\Nova\Copilot\ValueObjects\Intent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

final class CopilotDomainTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_input_analyzer_detects_text_payload(): void
    {
        $payload = [
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
        ];

        $input = (new InputAnalyzer())->analyzeArray($payload);

        $this->assertSame(InputType::TEXT, $input->type);
        $this->assertSame('Hola', $input->text);
        $this->assertSame('whatsapp', $input->channel);
        $this->assertNull($input->mediaId);
    }

    public function test_input_analyzer_detects_audio_payload(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'audio',
                                        'from' => '34600000000',
                                        'audio' => ['id' => 'audio-123'],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $input = (new InputAnalyzer())->analyzeArray($payload);

        $this->assertSame(InputType::AUDIO, $input->type);
        $this->assertSame('audio-123', $input->mediaId);
    }

    public function test_input_analyzer_detects_button_reply(): void
    {
        $payload = [
            'entry' => [
                [
                    'changes' => [
                        [
                            'value' => [
                                'messages' => [
                                    [
                                        'type' => 'interactive',
                                        'from' => '34600000000',
                                        'interactive' => [
                                            'button_reply' => ['id' => 'confirm'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $input = (new InputAnalyzer())->analyzeArray($payload);

        $this->assertSame('confirm', $input->text);
    }

    public function test_context_engine_loads_empty_context(): void
    {
        $engine = new ContextEngine();
        $context = $engine->load('+34600000000');

        $this->assertSame('+34600000000', $context->phone);
        $this->assertSame([], $context->workspace);
        $this->assertNull($context->activeCapability);
    }

    public function test_context_engine_persists_messages_and_workspace(): void
    {
        $engine = new ContextEngine();
        $engine->attachWorkspace('+34600000000', ['business_name' => 'Casa El Patio']);
        $engine->recordUserMessage('+34600000000', 'Ver gastos');
        $engine->recordAssistantMessage('+34600000000', 'Aquí tienes los gastos');

        $context = $engine->load('+34600000000');

        $this->assertSame('Casa El Patio', $context->workspace['business_name']);
        $this->assertCount(2, $context->history);
        $this->assertSame('user', $context->history[0]['role']);
    }

    public function test_intent_engine_detects_greeting(): void
    {
        $intent = $this->detect('Hola');

        $this->assertSame(IntentName::GREETING, $intent->name);
        $this->assertSame(Confidence::HIGH, $intent->confidence);
    }

    public function test_intent_engine_detects_power_menu(): void
    {
        $intent = $this->detect('menu');

        $this->assertSame(IntentName::MENU, $intent->name);
        $this->assertSame(Confidence::HIGH, $intent->confidence);
    }

    public function test_intent_engine_detects_operational_menu(): void
    {
        $intent = $this->detect('nvm');

        $this->assertSame(IntentName::OPERATIONAL_MENU, $intent->name);
        $this->assertSame(Confidence::HIGH, $intent->confidence);
    }

    public function test_intent_engine_resolves_operational_menu_number_selection(): void
    {
        $context = (new ConversationContext('+34600000000'))->withLastMenuType('operational');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, '0', 'whatsapp'), $context);

        $this->assertSame(IntentName::CONSULT, $intent->name);
        $this->assertSame('reservations', $intent->targetCapability);
        $this->assertSame(0, $intent->entities['menu_option']);
    }

    public function test_intent_engine_resolves_operational_menu_keyword_facturas(): void
    {
        $context = (new ConversationContext('+34600000000'))->withLastMenuType('operational');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, 'facturas', 'whatsapp'), $context);

        $this->assertSame(IntentName::CONSULT, $intent->name);
        $this->assertSame('invoices', $intent->targetCapability);
    }

    public function test_intent_engine_resolves_operational_menu_create_invoice(): void
    {
        $context = (new ConversationContext('+34600000000'))->withLastMenuType('operational');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, 'crear factura', 'whatsapp'), $context);

        $this->assertSame(IntentName::CREATE, $intent->name);
        $this->assertSame('invoices', $intent->targetCapability);
    }

    public function test_intent_engine_detects_contextual_follow_up_listado(): void
    {
        $context = (new ConversationContext('+34600000000'))->withActiveCapability('reservations');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, 'listado', 'whatsapp'), $context);

        $this->assertSame(IntentName::CONSULT, $intent->name);
        $this->assertSame('reservations', $intent->targetCapability);
        $this->assertTrue($intent->entities['contextual_follow_up']);
    }

    public function test_intent_engine_detects_contextual_follow_up_search(): void
    {
        $context = (new ConversationContext('+34600000000'))->withActiveCapability('invoices');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, 'search', 'whatsapp'), $context);

        $this->assertSame(IntentName::CONSULT, $intent->name);
        $this->assertSame('invoices', $intent->targetCapability);
        $this->assertTrue($intent->entities['contextual_follow_up']);
    }

    public function test_intent_engine_detects_create_expense(): void
    {
        $intent = $this->detect('He pagado 42€ en ferretería');

        $this->assertSame(IntentName::CREATE, $intent->name);
        $this->assertSame(Confidence::HIGH, $intent->confidence);
        $this->assertSame('expenses', $intent->targetCapability);
        $this->assertSame(42.0, $intent->entities['extracted_amount']);
    }

    public function test_intent_engine_detects_consult_reservations(): void
    {
        $intent = $this->detect('Muéstrame las reservas');

        $this->assertSame(IntentName::CONSULT, $intent->name);
        $this->assertSame('reservations', $intent->targetCapability);
    }

    public function test_intent_engine_uses_pending_operation_for_confirmation(): void
    {
        $context = new ConversationContext('+34600000000');
        $context = $context->withPendingOperation('delete.expenses');

        $intent = (new IntentEngine())->detect(new Input(InputType::TEXT, 'sí', 'whatsapp'), $context);

        $this->assertSame(IntentName::CONFIRM, $intent->name);
        $this->assertSame(Confidence::HIGH, $intent->confidence);
    }

    public function test_intent_engine_returns_unknown_for_unrelated_input(): void
    {
        $intent = $this->detect('xyz123 no sense');

        $this->assertSame(IntentName::UNKNOWN, $intent->name);
        $this->assertSame(Confidence::LOW, $intent->confidence);
    }

    public function test_action_engine_proposes_create_expense_action(): void
    {
        $intent = new Intent(IntentName::CREATE, Confidence::HIGH, ['capability' => 'expenses'], 'expenses');
        $context = new ConversationContext('+34600000000');

        $actions = (new ActionEngine())->propose($intent, $context);

        $this->assertCount(1, $actions);
        $this->assertSame('create', $actions[0]->id);
        $this->assertSame('capability.create', $actions[0]->operation);
        $this->assertSame('expenses', $actions[0]->parameters['capability']);
    }

    public function test_action_engine_delete_requires_confirmation(): void
    {
        $intent = new Intent(IntentName::DELETE, Confidence::HIGH, ['capability' => 'invoices'], 'invoices');
        $context = (new ConversationContext('+34600000000'))->withActiveEntity('invoice', '48');

        $actions = (new ActionEngine())->propose($intent, $context);

        $this->assertTrue($actions[0]->requiresConfirmation);
    }

    public function test_menu_engine_builds_workspace_main_menu(): void
    {
        $context = (new ConversationContext('+34600000000'))->withWorkspace([
            'business_name' => 'Casa El Patio',
            'capabilities' => [
                ['id' => 'reservations', 'name' => 'Reservas', 'icon' => '🏡'],
                ['id' => 'expenses', 'name' => 'Gastos', 'icon' => '💶'],
            ],
        ]);

        $menu = (new MenuEngine())->mainMenu($context);

        $this->assertSame('summary', $menu[0]['id']);
        $this->assertSame('reservations', $menu[1]['id']);
        $this->assertSame('expenses', $menu[2]['id']);
        $this->assertSame('settings', $menu[3]['id']);
    }

    public function test_menu_engine_builds_contextual_menu_for_active_capability(): void
    {
        $context = (new ConversationContext('+34600000000'))
            ->withActiveCapability('expenses')
            ->withActiveEntity('expense', '123');

        $menu = (new MenuEngine())->contextualMenu($context);

        $ids = array_map(static fn (array $item): string => $item['id'], $menu);
        $this->assertContains('list', $ids);
        $this->assertContains('create', $ids);
        $this->assertContains('edit', $ids);
        $this->assertContains('delete', $ids);
        $this->assertContains('back', $ids);
    }

    public function test_response_builder_generates_greeting_with_workspace_name(): void
    {
        $context = (new ConversationContext('+34600000000'))->withWorkspace([
            'business_name' => 'Casa El Patio',
            'capabilities' => [],
        ]);
        $intent = new Intent(IntentName::GREETING, Confidence::HIGH, [], null);

        $response = (new ResponseBuilder(new MenuEngine()))->build(
            new Input(InputType::TEXT, 'Hola', 'whatsapp'),
            $intent,
            [],
            $context,
        );

        $this->assertStringContainsString('Casa El Patio', $response->text);
        $this->assertNotEmpty($response->menu);
    }

    public function test_response_builder_generates_clarification_for_unknown_intent(): void
    {
        $intent = new Intent(IntentName::UNKNOWN, Confidence::LOW, [], null);
        $context = new ConversationContext('+34600000000');

        $response = (new ResponseBuilder(new MenuEngine()))->build(
            new Input(InputType::TEXT, 'asdf', 'whatsapp'),
            $intent,
            [],
            $context,
        );

        $this->assertStringContainsString('No estoy seguro', $response->text);
        $this->assertNotEmpty($response->menu);
    }

    private function detect(string $text): Intent
    {
        return (new IntentEngine())->detect(
            new Input(InputType::TEXT, $text, 'whatsapp'),
            new ConversationContext('+34600000000'),
        );
    }
}
