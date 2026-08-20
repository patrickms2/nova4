<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

final readonly class Action
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public string $id,
        public string $label,
        public string $operation,
        public array $parameters = [],
        public ?string $description = null,
        public bool $requiresConfirmation = false,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'label' => $this->label,
            'description' => $this->description,
            'operation' => $this->operation,
            'parameters' => $this->parameters,
            'requires_confirmation' => $this->requiresConfirmation,
        ];
    }
}
