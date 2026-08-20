<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

final readonly class BusinessBlueprint
{
    /**
     * @param  array<int, string>  $capabilityIds
     * @param  array<int, string>  $businessTypes
     */
    public function __construct(
        public string $id,
        public string $name,
        public array $capabilityIds,
        public array $businessTypes = [],
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'capabilities' => $this->capabilityIds,
            'business_types' => $this->businessTypes ?: [$this->name],
        ];
    }
}
