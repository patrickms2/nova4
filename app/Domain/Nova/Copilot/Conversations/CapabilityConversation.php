<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

use App\Domain\Nova\Copilot\ValueObjects\Step;

interface CapabilityConversation
{
    public function capability(): string;

    /**
     * @return array<string, array<int, Step>>  operation => steps
     */
    public function definition(): array;

    public function startStep(string $operation): ?string;
}
