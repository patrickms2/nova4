<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

final readonly class ConversationResult
{
    /**
     * @param  array<int, Action>  $actions
     * @param  array<int, array<string, mixed>>  $menu
     */
    public function __construct(
        public string $text,
        public Conversation $conversation,
        public array $actions = [],
        public array $menu = [],
        public bool $requiresConfirmation = false,
    ) {}
}
