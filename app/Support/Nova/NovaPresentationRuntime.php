<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaBindingTarget;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use Illuminate\Support\Collection;

final class NovaPresentationRuntime
{
    /**
     * @return Collection<int, NovaBinding>
     */
    public function bindings(string $panelKey, string $role, NovaRepresentationType $representation): Collection
    {
        return NovaBinding::query()
            ->with(['group', 'capability'])
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->where('target_type', NovaBindingTarget::Capability)
            ->where('role', $role)
            ->where('representation', $representation)
            ->where('visible', true)
            ->orderBy('sort')
            ->get();
    }

    /**
     * @return array<string, array{label:string, icon:?string, capability:string}>
     */
    public function sectionMap(string $panelKey, string $role, NovaRepresentationType $representation): array
    {
        $result = [];

        foreach ($this->bindings($panelKey, $role, $representation) as $binding) {
            $capability = $binding->capability;
            if (! $capability) {
                continue;
            }

            $section = (string) ($capability->settings['section'] ?? str($capability->key)->afterLast('.'));
            $result[$section] = [
                'label' => (string) ($binding->settings['label'] ?? $capability->name),
                'icon' => $binding->settings['icon'] ?? $capability->icon,
                'capability' => $capability->key,
            ];
        }

        return $result;
    }
}
