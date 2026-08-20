<?php

declare(strict_types=1);

namespace App\Domain\Nova\Copilot;

use App\Domain\Nova\Copilot\ValueObjects\PipelineStep;

final class PipelineTracer
{
    /** @var array<string, float> */
    private array $timers = [];

    /** @var array<int, PipelineStep> */
    private array $steps = [];

    /** @var array<int, array<string, mixed>> */
    private array $pendingLogs = [];

    public function start(string $phase): void
    {
        $this->timers[$phase] = microtime(true);
        $this->pendingLogs = [];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function finish(string $phase, array $data = []): void
    {
        $startedAt = $this->timers[$phase] ?? microtime(true);
        $durationMs = (microtime(true) - $startedAt) * 1000;

        $this->steps[] = new PipelineStep(
            name: $phase,
            durationMs: $durationMs,
            data: $data,
            logs: $this->pendingLogs,
        );

        $this->pendingLogs = [];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function log(string $message, array $context = []): void
    {
        $this->pendingLogs[] = [
            'message' => $message,
            'context' => $context,
            'timestamp' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (PipelineStep $step): array => $step->toArray(),
            $this->steps
        );
    }

    /**
     * @return array<int, PipelineStep>
     */
    public function steps(): array
    {
        return $this->steps;
    }

    public function totalDurationMs(): float
    {
        $total = 0.0;

        foreach ($this->steps as $step) {
            $total += $step->durationMs;
        }

        return $total;
    }
}
