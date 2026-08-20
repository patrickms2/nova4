<?php

declare(strict_types=1);

namespace App\Services\Nova;

use App\Domain\Nova\Copilot\CopilotOrchestrator;
use App\Domain\Nova\Copilot\PipelineTracer;
use App\Domain\Nova\Copilot\ValueObjects\ConversationKernelResult;
use App\Domain\Nova\Copilot\ValueObjects\Conversation;
use App\Domain\Nova\Copilot\ValueObjects\Intent;
use Illuminate\Http\Request;

final class NovaConversationKernel
{
    public function process(
        string $message,
        string $channel,
        string $user,
        ?Request $request = null,
        ?array $workspace = null,
        bool $debug = false,
    ): ConversationKernelResult {
        $startedAt = microtime(true);
        $tracer = new PipelineTracer();
        $tracer->log('Kernel process started', [
            'channel' => $channel,
            'user' => $user,
            'message_length' => strlen($message),
        ]);

        $orchestrator = app()->make(CopilotOrchestrator::class, ['tracer' => $tracer]);
        $incomingRequest = $request ?? $this->buildTextRequest($message, $channel);

        $result = $orchestrator->handle($incomingRequest, $user, $workspace, $channel);

        $intent = isset($result['intent']) ? new Intent(
            name: \App\Domain\Nova\Copilot\Enums\IntentName::from($result['intent']['name']),
            confidence: \App\Domain\Nova\Copilot\Enums\Confidence::from($result['intent']['confidence']),
            entities: $result['intent']['entities'] ?? [],
            targetCapability: $result['intent']['target_capability'] ?? null,
        ) : null;

        $conversation = isset($result['context']['active_conversation'])
            ? Conversation::fromArray($result['context']['active_conversation'])
            : null;

        $responseArray = $result['response'] ?? [];

        $handler = null;
        foreach ($result['pipeline'] ?? [] as $step) {
            if (($step['name'] ?? '') === 'handler') {
                $handler = $step['data']['handler'] ?? null;
            }
        }

        $input = ['text' => $message, 'channel' => $channel, 'user' => $user];
        foreach ($result['pipeline'] ?? [] as $step) {
            if (($step['name'] ?? '') === 'normalize') {
                $input = array_merge($input, $step['data'] ?? []);
            }
        }

        $context = [
            'phone' => $result['context']['phone'] ?? $user,
            'active_capability' => $result['context']['active_capability'] ?? null,
            'active_entity_type' => $result['context']['active_entity_type'] ?? null,
            'active_entity_id' => $result['context']['active_entity_id'] ?? null,
            'pending_operation' => $result['context']['pending_operation'] ?? null,
            'active_conversation' => $result['context']['active_conversation'] ?? null,
            'history' => $result['context']['history'] ?? [],
            'last_menu_type' => $result['context']['last_menu_type'] ?? null,
        ];

        $memory = [
            'history' => $context['history'],
            'active_capability' => $context['active_capability'],
            'active_entity_type' => $context['active_entity_type'],
            'active_entity_id' => $context['active_entity_id'],
            'pending_operation' => $context['pending_operation'],
            'last_menu_type' => $context['last_menu_type'],
        ];

        $tracer->log('Kernel process finished', [
            'reply_length' => strlen($responseArray['text'] ?? ''),
            'conversation_active' => $conversation !== null,
        ]);

        return new ConversationKernelResult(
            reply: $responseArray['text'] ?? '',
            pipeline: $result['pipeline'] ?? [],
            logs: $this->collectLogs($result['pipeline'] ?? []),
            intent: $intent,
            conversation: $conversation,
            memory: $memory,
            input: $input,
            context: $context,
            handler: $handler,
            planner: null,
            channel: $channel,
            user: $user,
            conversationId: $user,
            totalDurationMs: (microtime(true) - $startedAt) * 1000,
            debug: ['raw' => $result],
            response: $responseArray,
        );
    }

    private function buildTextRequest(string $message, string $channel): Request
    {
        return Request::create(
            uri: '/nova/debug/chat',
            method: 'POST',
            parameters: [
                'message' => [
                    'type' => 'text',
                    'from' => 'debug',
                    'text' => ['body' => $message],
                ],
                'channel' => $channel,
            ],
        );
    }

    /**
     * @param  array<int, array<string, mixed>>  $pipeline
     * @return array<int, array<string, mixed>>
     */
    private function collectLogs(array $pipeline): array
    {
        $logs = [];

        foreach ($pipeline as $step) {
            foreach ($step['logs'] ?? [] as $log) {
                $logs[] = $log + ['phase' => $step['name']];
            }
        }

        return $logs;
    }
}
