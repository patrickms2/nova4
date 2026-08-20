<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaCapability;
use App\Models\Nova\NovaPanel;
use App\Models\Nova\NovaTool;

final class NovaRuntime
{
    public function capabilityEnabled(
        string $panelKey,
        string $role,
        NovaRepresentationType $representation,
        string $capabilityKey
    ): bool {
        return NovaBinding::query()
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->whereHas('capability', fn ($query) => $query->where('key', $capabilityKey))
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $role)
            ->where('representation', $representation)
            ->where('visible', true)
            ->exists();
    }

    public function toolEnabled(
        string $panelKey,
        string $role,
        NovaRepresentationType $representation,
        string $capabilityKey,
        string $toolKey
    ): bool {
        if (! $this->capabilityEnabled($panelKey, $role, $representation, $capabilityKey)) {
            return false;
        }

        $capability = NovaCapability::query()->where('key', $capabilityKey)->first();

        if (! $capability) {
            return false;
        }

        $tool = NovaTool::query()
            ->where('capability_id', $capability->id)
            ->where('key', $toolKey)
            ->first();

        if (! $tool) {
            return false;
        }

        $explicitBinding = NovaBinding::query()
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->where('tool_id', $tool->id)
            ->where('target_type', NovaBindingTarget::Tool)
            ->where('role', $role)
            ->where('representation', $representation)
            ->first();

        return $explicitBinding?->visible ?? true;
    }
}
