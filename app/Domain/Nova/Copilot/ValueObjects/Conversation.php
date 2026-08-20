<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\Enums\ConversationStatus;

final readonly class Conversation
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $capability,
        public string $operation,
        public string $currentStep,
        public array $data = [],
        public ConversationStatus $status = ConversationStatus::ACTIVE,
        public ?string $startedAt = null,
        public ?string $lastInteractionAt = null,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            capability: (string) ($payload['capability'] ?? ''),
            operation: (string) ($payload['operation'] ?? ''),
            currentStep: (string) ($payload['current_step'] ?? ''),
            data: (array) ($payload['data'] ?? []),
            status: isset($payload['status']) && is_string($payload['status'])
                ? ConversationStatus::from($payload['status'])
                : ConversationStatus::ACTIVE,
            startedAt: isset($payload['started_at']) && is_string($payload['started_at']) ? $payload['started_at'] : null,
            lastInteractionAt: isset($payload['last_interaction_at']) && is_string($payload['last_interaction_at'])
                ? $payload['last_interaction_at']
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'capability' => $this->capability,
            'operation' => $this->operation,
            'current_step' => $this->currentStep,
            'data' => $this->data,
            'status' => $this->status->value,
            'started_at' => $this->startedAt,
            'last_interaction_at' => $this->lastInteractionAt,
        ];
    }

    public function withCurrentStep(string $step): self
    {
        return new self(
            capability: $this->capability,
            operation: $this->operation,
            currentStep: $step,
            data: $this->data,
            status: $this->status,
            startedAt: $this->startedAt,
            lastInteractionAt: $this->lastInteractionAt,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function withData(array $data): self
    {
        return new self(
            capability: $this->capability,
            operation: $this->operation,
            currentStep: $this->currentStep,
            data: $data,
            status: $this->status,
            startedAt: $this->startedAt,
            lastInteractionAt: $this->lastInteractionAt,
        );
    }

    public function withStatus(ConversationStatus $status): self
    {
        return new self(
            capability: $this->capability,
            operation: $this->operation,
            currentStep: $this->currentStep,
            data: $this->data,
            status: $status,
            startedAt: $this->startedAt,
            lastInteractionAt: $this->lastInteractionAt,
        );
    }

    public function touch(): self
    {
        return new self(
            capability: $this->capability,
            operation: $this->operation,
            currentStep: $this->currentStep,
            data: $this->data,
            status: $this->status,
            startedAt: $this->startedAt ?? now()->toIso8601String(),
            lastInteractionAt: now()->toIso8601String(),
        );
    }
}
