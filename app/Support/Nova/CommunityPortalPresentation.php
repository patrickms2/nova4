<?php
declare(strict_types=1);

namespace App\Support\Nova;

use App\Support\CommunityCapabilityRuntime;

final class CommunityPortalPresentation
{
    public function forRole(string $role): array
    {
        $enabled = app(CommunityCapabilityRuntime::class)->enabledSections($role);

        return [
            'novaEnabledSections' => $enabled,
            'novaRuntimeConfigured' => $enabled !== [],
        ];
    }
}
