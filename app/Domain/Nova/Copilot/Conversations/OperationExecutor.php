<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\Conversations;

interface OperationExecutor
{
    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function execute(string $capability, string $operation, array $data, string $phone): array;

    public function supports(string $capability, string $operation): bool;
}
