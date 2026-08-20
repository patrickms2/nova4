<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

final readonly class Response
{
    /**
     * @param  array<int, Action>  $actions
     * @param  array<int, array<string, mixed>>  $menu
     */
    public function __construct(
        public string $text,
        public array $actions = [],
        public array $menu = [],
        public bool $requiresConfirmation = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'actions' => array_map(
                static fn (Action $action): array => $action->toArray(),
                $this->actions,
            ),
            'menu' => $this->menu,
            'requires_confirmation' => $this->requiresConfirmation,
        ];
    }
}
