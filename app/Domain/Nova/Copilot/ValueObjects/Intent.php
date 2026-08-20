<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\Enums\Confidence;
use App\Domain\Nova\Copilot\Enums\IntentName;

final readonly class Intent
{
    /**
     * @param  array<string, mixed>  $entities
     */
    public function __construct(
        public IntentName $name,
        public Confidence $confidence,
        public array $entities = [],
        public ?string $targetCapability = null,
    ) {}

    public function requiresConfirmation(): bool
    {
        return in_array($this->name, [IntentName::DELETE, IntentName::SEND], true);
    }

    public function isActionable(): bool
    {
        return $this->name !== IntentName::UNKNOWN && $this->confidence !== Confidence::LOW;
    }

    public function withTargetCapability(?string $capability): self
    {
        return new self(
            name: $this->name,
            confidence: $this->confidence,
            entities: $this->entities,
            targetCapability: $capability,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name->value,
            'confidence' => $this->confidence->value,
            'entities' => $this->entities,
            'target_capability' => $this->targetCapability,
        ];
    }
}
