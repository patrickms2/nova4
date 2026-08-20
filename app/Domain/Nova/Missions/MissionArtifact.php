<?php

declare(strict_types=1);

namespace App\Domain\Nova\Missions;

use Illuminate\Support\Str;

final readonly class MissionArtifact
{
    public function __construct(
        public string $id,
        public string $name,
        public string $path,
        public string $type,
        public string $createdAt,
    ) {}

    public static function make(string $name, string $goal): self
    {
        return new self(
            id: (string) Str::uuid(),
            name: $name,
            path: 'missions/'.Str::slug($goal).'/'.$name,
            type: strtoupper((string) pathinfo($name, PATHINFO_EXTENSION)),
            createdAt: now()->toIso8601String(),
        );
    }

    /** @return array<string, string> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'path' => $this->path,
            'type' => $this->type,
            'created_at' => $this->createdAt,
        ];
    }
}
