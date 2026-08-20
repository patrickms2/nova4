<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot\ValueObjects;

final readonly class PipelineStep
{
    /**
     * @param  array<string, mixed>  $data
     * @param  array<int, array<string, mixed>>  $logs
     */
    public function __construct(
        public string $name,
        public float $durationMs,
        public array $data = [],
        public array $logs = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'duration_ms' => round($this->durationMs, 3),
            'data' => $this->data,
            'logs' => $this->logs,
        ];
    }
}
