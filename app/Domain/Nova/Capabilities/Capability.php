<?php

declare(strict_types=1);

namespace App\Domain\Nova\Capabilities;

final readonly class Capability
{
    /**
     * @param  array<int, string>  $requiredAgents
     * @param  array<int, string>  $requiredConnectors
     * @param  array<int, string>  $requiredProviders
     * @param  array<int, string>  $requiredArtifacts
     * @param  array<int, string>  $dependencies
     * @param  array<int, string>  $supportedBusinessTypes
     * @param  array<int, string>  $intentTerms
     */
    public function __construct(
        public string $id,
        public string $name,
        public string $description,
        public string $category,
        public array $requiredAgents,
        public array $requiredConnectors,
        public array $requiredProviders,
        public array $requiredArtifacts,
        public array $dependencies,
        public int $priority,
        public int $estimatedDuration,
        public array $supportedBusinessTypes,
        public string $status,
        public array $intentTerms,
        public string $icon,
        public string $tool,
        public bool $requiresApproval = false,
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return get_object_vars($this);
    }

    public function supports(string $businessType): bool
    {
        return $this->supportedBusinessTypes === []
            || in_array(strtolower($businessType), array_map('strtolower', $this->supportedBusinessTypes), true);
    }
}
