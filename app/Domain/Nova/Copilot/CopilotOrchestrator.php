<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\Conversations\ConversationRegistry;
use App\Domain\Nova\Copilot\Conversations\NovaFactuOperationExecutor;
use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\ConversationStatus;
use App\Domain\Nova\Copilot\Enums\IntentName;
use App\Domain\Nova\Copilot\ValueObjects\Conversation;
use App\Domain\Nova\Copilot\ValueObjects\ConversationContext;
use App\Domain\Nova\Copilot\ValueObjects\ConversationResult;
use App\Domain\Nova\Copilot\ValueObjects\Input;
use App\Domain\Nova\Copilot\ValueObjects\Intent;
use App\Domain\Nova\Copilot\ValueObjects\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final readonly class CopilotOrchestrator
{
    private ConversationEngine $conversationEngine;

    public function __construct(
        private InputAnalyzer $inputAnalyzer,
        private ContextEngine $contextEngine,
        private IntentEngine $intentEngine,
        private ActionEngine $actionEngine,
        private ResponseBuilder $responseBuilder,
        private MediaProcessor $mediaProcessor,
        private ?PipelineTracer $tracer = null,
    ) {
        $this->conversationEngine = new ConversationEngine(
            new ConversationRegistry(),
            [new NovaFactuOperationExecutor()],
        );
    }

    /**
     * @param  array<string, mixed>|null  $workspace
     * @return array<string, mixed>
     */
    public function handle(Request $request, string $phone, ?array $workspace = null, string $channel = 'whatsapp'): array
    {
        $this->tracer?->start('normalize');
        $input = $this->inputAnalyzer->analyze($request, $channel);
        $input = $this->mediaProcessor->process($request, $input);
        $this->tracer?->finish('normalize', [
            'type' => $input->type->value,
            'text' => $input->text,
        ]);

        Log::info('Copilot v2: input analyzed', [
            'phone' => $phone,
            'type' => $input->type->value,
            'text' => $input->text,
        ]);

        $context = $this->loadOrInitializeContext($phone, $workspace);
        $context = $context->recordMessage('user', $input->text);

        $this->tracer?->start('context_resolver');
        $result = $this->processInput($input, $context, $phone);
        $this->tracer?->finish('context_resolver', [
            'active_capability' => $result['context']->activeCapability,
            'active_conversation' => $result['context']->activeConversation?->toArray(),
        ]);

        $this->tracer?->start('response_formatter');
        $response = $result['response'];
        $this->tracer?->finish('response_formatter', [
            'text' => $response->text,
            'menu_count' => count($response->menu),
            'action_count' => count($response->actions),
        ]);

        $context = $result['context'];
        $intent = $result['intent'];

        $context = $context->recordMessage('assistant', $response->text);
        $this->contextEngine->save($context);

        Log::info('Copilot v2: response generated', [
            'phone' => $phone,
            'intent' => $intent->name->value,
            'confidence' => $intent->confidence->value,
            'in_conversation' => $context->activeConversation !== null,
        ]);

        return [
            'success' => true,
            'input' => [
                'type' => $input->type->value,
                'text' => $input->text,
                'channel' => $input->channel,
            ],
            'intent' => [
                'name' => $intent->name->value,
                'confidence' => $intent->confidence->value,
                'entities' => $intent->entities,
                'target_capability' => $intent->targetCapability,
            ],
            'response' => $response->toArray(),
            'context' => [
                'phone' => $context->phone,
                'active_capability' => $context->activeCapability,
                'active_entity_type' => $context->activeEntityType,
                'active_entity_id' => $context->activeEntityId,
                'pending_operation' => $context->pendingOperation,
                'active_conversation' => $context->activeConversation?->toArray(),
                'history' => $context->history,
                'last_menu_type' => $context->lastMenuType,
            ],
            'pipeline' => $this->tracer?->toArray(),
            'timings' => [
                'total_ms' => $this->tracer !== null ? round($this->tracer->totalDurationMs(), 3) : 0.0,
            ],
        ];
    }

    /**
     * @return array{response: Response, context: ConversationContext, intent: Intent}
     */
    private function processInput(Input $input, ConversationContext $context, string $phone): array
    {
        if ($this->isGlobalExit($input)) {
            $context = $context->withActiveConversation(null);
        }

        if (
            $context->activeConversation !== null
            && ! in_array($context->activeConversation->status, [ConversationStatus::COMPLETED, ConversationStatus::CANCELLED, ConversationStatus::TIMED_OUT], true)
            && ! $this->isGlobalCommand($input)
        ) {
            return $this->continueConversation($input, $context, $phone);
        }

        $this->tracer?->start('intent_detection');
        $intent = $this->intentEngine->detect(
            new Input($input->type, $input->text, $input->channel),
            $context
        );
        $this->tracer?->finish('intent_detection', [
            'intent' => $intent->name->value,
            'confidence' => $intent->confidence->value,
            'target_capability' => $intent->targetCapability,
            'entities' => $intent->entities,
        ]);

        if (in_array($intent->name, [IntentName::GREETING, IntentName::MENU, IntentName::OPERATIONAL_MENU, IntentName::UNKNOWN], true)) {
            $response = $this->buildResponseFromIntent($input, $intent, $context);
            $context = $this->updateContextFromIntent($context, $intent, $response);

            return ['response' => $response, 'context' => $context, 'intent' => $intent];
        }

        $operation = $this->intentOperation($intent);

        if ($operation === null || $intent->targetCapability === null) {
            $response = $this->buildResponseFromIntent($input, $intent, $context);
            $context = $this->updateContextFromIntent($context, $intent, $response);

            return ['response' => $response, 'context' => $context, 'intent' => $intent];
        }

        return $this->startConversation($intent->targetCapability, $operation, $context, $phone, $intent);
    }

    /**
     * @return array{response: Response, context: ConversationContext, intent: Intent}
     */
    private function continueConversation(Input $input, ConversationContext $context, string $phone): array
    {
        $this->tracer?->start('conversation');
        $conversationResult = $this->conversationEngine->proceed(
            $context->activeConversation,
            $input
        );
        $this->tracer?->finish('conversation', [
            'capability' => $context->activeConversation->capability,
            'operation' => $context->activeConversation->operation,
            'step' => $conversationResult->conversation->currentStep,
            'status' => $conversationResult->conversation->status->value,
        ]);

        $context = $context->withActiveConversation(
            $conversationResult->conversation->status === ConversationStatus::ACTIVE
                ? $conversationResult->conversation
                : null
        );

        $response = new Response(
            text: $conversationResult->text,
            actions: $conversationResult->actions,
            menu: $conversationResult->menu,
            requiresConfirmation: $conversationResult->requiresConfirmation,
        );

        return [
            'response' => $response,
            'context' => $context,
            'intent' => new Intent(IntentName::REPLY, Confidence::HIGH, ['in_conversation' => true], $context->activeCapability),
        ];
    }

    /**
     * @return array{response: Response, context: ConversationContext, intent: Intent}
     */
    private function startConversation(string $capability, string $operation, ConversationContext $context, string $phone, Intent $originalIntent): array
    {
        $this->tracer?->start('conversation');
        $conversationResult = $this->conversationEngine->start($capability, $operation);
        $this->tracer?->finish('conversation', [
            'capability' => $capability,
            'operation' => $operation,
            'step' => $conversationResult->conversation->currentStep,
            'status' => $conversationResult->conversation->status->value,
        ]);

        if ($conversationResult->conversation->currentStep === '') {
            $response = new Response(text: $conversationResult->text);

            return ['response' => $response, 'context' => $context, 'intent' => $originalIntent];
        }

        $context = $context
            ->withActiveCapability($capability)
            ->withActiveConversation($conversationResult->conversation);

        if ($conversationResult->conversation->status === ConversationStatus::COMPLETED) {
            return [
                'response' => new Response(
                    text: $conversationResult->text,
                    menu: $conversationResult->menu,
                ),
                'context' => $context->withActiveConversation(null),
                'intent' => $originalIntent,
            ];
        }

        $response = new Response(
            text: $conversationResult->text,
            menu: $conversationResult->menu,
        );

        return [
            'response' => $response,
            'context' => $context,
            'intent' => $originalIntent,
        ];
    }

    private function buildResponseFromIntent(Input $input, Intent $intent, ConversationContext $context): Response
    {
        $actions = $this->actionEngine->propose($intent, $context);

        return $this->responseBuilder->build($input, $intent, $actions, $context);
    }

    private function isGlobalExit(Input $input): bool
    {
        return in_array(mb_strtolower(trim($input->text)), ['cancelar', 'salir', 'reset'], true);
    }

    private function isGlobalCommand(Input $input): bool
    {
        return in_array(mb_strtolower(trim($input->text)), ['menu', 'nvm', 'opciones'], true);
    }

    private function intentOperation(Intent $intent): ?string
    {
        return match ($intent->name) {
            IntentName::CONSULT => 'consult',
            IntentName::CREATE => 'create',
            IntentName::EDIT => 'edit',
            IntentName::DELETE => 'delete',
            IntentName::SEND => 'send',
            IntentName::IMPORT => 'import',
            IntentName::ANALYZE => 'analyze',
            IntentName::CONFIGURE => 'configure',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>|null  $workspace
     */
    private function loadOrInitializeContext(string $phone, ?array $workspace): ConversationContext
    {
        $context = $this->contextEngine->load($phone);

        if ($workspace !== null && $context->workspace === []) {
            $context = $context->withWorkspace($workspace);
        }

        return $context;
    }

    private function updateContextFromIntent(
        ConversationContext $context,
        Intent $intent,
        Response $response,
    ): ConversationContext {
        $capability = $intent->targetCapability ?? $context->activeCapability;

        if ($intent->confidence === Confidence::HIGH && $capability !== null) {
            $context = $context->withActiveCapability($capability);
        }

        if ($response->requiresConfirmation && $intent->name !== IntentName::CONFIRM && $intent->name !== IntentName::CANCEL) {
            $context = $context->withPendingOperation($intent->name->value.($capability ? '.'.$capability : ''));
        }

        if (in_array($intent->name, [IntentName::CONFIRM, IntentName::CANCEL], true)) {
            $context = $context->withPendingOperation(null);
        }

        $lastMenuType = match ($intent->name) {
            IntentName::MENU => 'contextual',
            IntentName::OPERATIONAL_MENU => 'operational',
            default => null,
        };
        $context = $context->withLastMenuType($lastMenuType);

        return $context;
    }

}
