<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use Illuminate\Support\Collection;

final class NovaFilamentNavigation
{
    /**
     * @return Collection<string, Collection<int, NovaBinding>>
     */
    public function forRole(string $panelKey, string $role = 'manager'): Collection
    {
        return NovaBinding::query()
            ->with(['group', 'capability'])
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $role)
            ->where('representation', NovaRepresentationType::Filament)
            ->where('visible', true)
            ->orderBy('sort')
            ->get()
            ->groupBy(fn (NovaBinding $binding): string => $binding->group?->name ?? 'General');
    }

    public function capabilityVisible(string $panelKey, string $capabilityKey, string $role = 'manager'): bool
    {
        return NovaBinding::query()
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->whereHas('capability', fn ($query) => $query->where('key', $capabilityKey))
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $role)
            ->where('representation', NovaRepresentationType::Filament)
            ->where('visible', true)
            ->exists();
    }
}
