<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Nova\NovaRepresentationType;
use App\Support\Nova\NovaDefinitionService;
use App\Support\Nova\NovaRuntime;

final class CommunityCapabilityRuntime
{
    /** @var array<string, string> */
    private const SECTION_CAPABILITIES = [
        'properties' => 'community.properties',
        'documents' => 'community.documents',
        'fees' => 'community.fees',
        'communities' => 'community.communities',
        'plans' => 'community.plans',
        'work' => 'community.work-orders',
        'incidents' => 'community.incidents',
        'tickets' => 'community.tickets',
        'appointments' => 'community.appointments',
        'shifts' => 'community.shifts',
        'attendance' => 'community.attendance',
        'expenses' => 'community.expenses',
    ];

    public function enabledSections(string $role): array
    {
        app(NovaDefinitionService::class)->ensureCommunityDefinition();

        return collect(self::SECTION_CAPABILITIES)
            ->filter(fn (string $capability): bool => app(NovaRuntime::class)->capabilityEnabled(
                'community',
                $role,
                NovaRepresentationType::Livewire,
                $capability,
            ))
            ->keys()
            ->values()
            ->all();
    }

    public function sectionEnabled(string $role, string $section): bool
    {
        if ($section === 'home') {
            return true;
        }

        return in_array($section, $this->enabledSections($role), true);
    }
}
