<?php

declare(strict_types=1);

namespace App\Support\Nova;

use App\Enums\Nova\NovaRepresentationType;

final class CommunityPortalPresentation
{
    public function forRole(string $role): array
    {
        $map = app(NovaPresentationRuntime::class)->sectionMap(
            'community',
            $role,
            NovaRepresentationType::Livewire,
        );

        return [
            'novaEnabledSections' => array_keys($map),
            'novaSectionMap' => $map,
        ];
    }
}
