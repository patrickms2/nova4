<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

use App\Domain\Nova\Copilot\Enums\InputType;

final readonly class Step
{
    /**
     * @param  array<int, string>  $acceptedInputs
     * @param  array<int, int>  $acceptedNumbers
     * @param  array<string, string>  $acceptedSynonyms  maps synonym to canonical value
     * @param  array<string, string|null>  $branches  maps input value to next step key; _default, _cancel, _finish
     */
    public function __construct(
        public string $key,
        public string $prompt,
        public InputType $expectedInput = InputType::TEXT,
        public array $acceptedInputs = [],
        public array $acceptedNumbers = [],
        public array $acceptedSynonyms = [],
        public ?string $previousStep = null,
        public ?string $nextStep = null,
        public array $branches = [],
        public bool $isFinal = false,
        public bool $isCancel = false,
        public ?string $fallbackPrompt = null,
    ) {}
}
