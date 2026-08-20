<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final readonly class MissionEvent
{
    public function __construct(
        public string $id,
        public string $type,
        public string $title,
        public string $description,
        public string $context,
        public string $occurredAt,
    ) {}

    public static function make(string $type, string $title, string $description, string $context): self
    {
        return new self(
            id: (string) Str::uuid(),
            type: $type,
            title: $title,
            description: $description,
            context: $context,
            occurredAt: now()->toIso8601String(),
        );
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'description' => $this->description,
            'context' => $this->context,
            'occurred_at' => $this->occurredAt,
            'time' => Carbon::parse($this->occurredAt)->format('H:i:s'),
        ];
    }
}
