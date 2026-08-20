<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaRepresentationStatus;
use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaRepresentation;
use Illuminate\Support\Collection;

final class NovaRepresentationRuntime
{
    /**
     * @return Collection<int,NovaRepresentation>
     */
    public function filamentForPanel(string $panelKey): Collection
    {
        return NovaRepresentation::query()
            ->with(['resource', 'capability'])
            ->where('type', NovaRepresentationType::Filament)
            ->whereHas('panel', fn ($query) => $query->where('key', $panelKey))
            ->whereIn('status', [
                NovaRepresentationStatus::Matched,
                NovaRepresentationStatus::Configured,
            ])
            ->orderByRaw('COALESCE(navigation_sort, 9999)')
            ->orderBy('name')
            ->get();
    }
}
