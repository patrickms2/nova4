<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\ValueObjects\Conversation;
use App\Domain\Nova\Copilot\ValueObjects\Intent;

final readonly class ConversationKernelResult
{
    /**
     * @param  array<int, array<string, mixed>>  $pipeline
     * @param  array<int, array<string, mixed>>  $logs
     * @param  array<string, mixed>  $input
     * @param  array<string, mixed>  $memory
     * @param  array<string, mixed>  $context
     * @param  array<string, mixed>  $response
     * @param  array<string, mixed>  $debug
     */
    public function __construct(
        public string $reply,
        public array $pipeline,
        public array $logs,
        public ?Intent $intent,
        public ?Conversation $conversation,
        public array $memory,
        public array $input,
        public array $context,
        public ?string $handler = null,
        public ?string $planner = null,
        public string $channel = 'unknown',
        public string $user = 'unknown',
        public string $conversationId = '',
        public float $totalDurationMs = 0.0,
        public array $debug = [],
        public array $response = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'success' => true,
            'reply' => $this->reply,
            'input' => $this->input,
            'normalized' => $this->input,
            'pipeline' => $this->pipeline,
            'logs' => $this->logs,
            'intent' => $this->intent?->toArray(),
            'entities' => $this->intent?->entities ?? [],
            'conversation' => $this->conversation?->toArray(),
            'context' => $this->context,
            'memory' => $this->memory,
            'handler' => $this->handler,
            'planner' => $this->planner,
            'channel' => $this->channel,
            'user' => $this->user,
            'conversation_id' => $this->conversationId,
            'timings' => [
                'total_ms' => round($this->totalDurationMs, 3),
                'steps' => $this->pipeline,
            ],
            'debug' => $this->debug,
            'response' => $this->response,
        ];
    }
}
