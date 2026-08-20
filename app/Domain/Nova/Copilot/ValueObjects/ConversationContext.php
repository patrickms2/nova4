<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\Enums\ConversationStatus;
use App\Domain\Nova\Copilot\ValueObjects\Conversation as ActiveConversation;

final class ConversationContext
{
    /**
     * @param  array<string, mixed>  $workspace
     * @param  array<int, array{role: string, text: string, created_at: string}>  $history
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $phone,
        public array $workspace = [],
        public ?string $activeCapability = null,
        public ?string $activeEntityType = null,
        public ?string $activeEntityId = null,
        public ?string $pendingOperation = null,
        public array $history = [],
        public array $metadata = [],
        public ?string $lastInteractionAt = null,
        public ?string $lastMenuType = null,
        public ?ActiveConversation $activeConversation = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(string $phone, array $data): self
    {
        return new self(
            phone: $phone,
            workspace: (array) ($data['workspace'] ?? []),
            activeCapability: isset($data['active_capability']) && is_string($data['active_capability']) ? $data['active_capability'] : null,
            activeEntityType: isset($data['active_entity_type']) && is_string($data['active_entity_type']) ? $data['active_entity_type'] : null,
            activeEntityId: isset($data['active_entity_id']) && is_string($data['active_entity_id']) ? $data['active_entity_id'] : null,
            pendingOperation: isset($data['pending_operation']) && is_string($data['pending_operation']) ? $data['pending_operation'] : null,
            history: (array) ($data['history'] ?? []),
            metadata: (array) ($data['metadata'] ?? []),
            lastInteractionAt: isset($data['last_interaction_at']) && is_string($data['last_interaction_at']) ? $data['last_interaction_at'] : null,
            lastMenuType: isset($data['last_menu_type']) && is_string($data['last_menu_type']) ? $data['last_menu_type'] : null,
            activeConversation: isset($data['active_conversation']) && is_array($data['active_conversation'])
                ? ActiveConversation::fromArray($data['active_conversation'])
                : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'workspace' => $this->workspace,
            'active_capability' => $this->activeCapability,
            'active_entity_type' => $this->activeEntityType,
            'active_entity_id' => $this->activeEntityId,
            'pending_operation' => $this->pendingOperation,
            'history' => $this->history,
            'metadata' => $this->metadata,
            'last_interaction_at' => $this->lastInteractionAt,
            'last_menu_type' => $this->lastMenuType,
            'active_conversation' => $this->activeConversation?->toArray(),
        ];
    }

    public function withActiveCapability(?string $capability): self
    {
        $clone = clone $this;
        $clone->activeCapability = $capability;

        return $clone;
    }

    public function withActiveEntity(?string $type, ?string $id): self
    {
        $clone = clone $this;
        $clone->activeEntityType = $type;
        $clone->activeEntityId = $id;

        return $clone;
    }

    public function withPendingOperation(?string $operation): self
    {
        $clone = clone $this;
        $clone->pendingOperation = $operation;

        return $clone;
    }

    public function withWorkspace(array $workspace): self
    {
        $clone = clone $this;
        $clone->workspace = $workspace;

        return $clone;
    }

    public function withLastMenuType(?string $type): self
    {
        $clone = clone $this;
        $clone->lastMenuType = $type;

        return $clone;
    }

    public function withActiveConversation(?ActiveConversation $conversation): self
    {
        $clone = clone $this;
        $clone->activeConversation = $conversation;

        return $clone;
    }

    public function withMetadata(array $metadata): self
    {
        $clone = clone $this;
        $clone->metadata = $metadata;

        return $clone;
    }

    public function recordMessage(string $role, string $text): self
    {
        $clone = clone $this;
        $clone->history[] = [
            'role' => $role,
            'text' => $text,
            'created_at' => now()->toIso8601String(),
        ];
        $clone->history = array_slice($clone->history, -20);
        $clone->lastInteractionAt = now()->toIso8601String();

        return $clone;
    }
}
