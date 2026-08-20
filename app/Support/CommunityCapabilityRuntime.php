<?php
declare(strict_types=1);

namespace App\Support;

use App\Enums\Nova\NovaRepresentationType;
use App\Models\Nova\NovaBinding;
use App\Models\Nova\NovaPanel;
use App\Support\Nova\NovaRuntime;

final class CommunityCapabilityRuntime
{
    private const MAP = [
        'properties'=>'community.properties',
        'documents'=>'community.documents',
        'fees'=>'community.fees',
        'communities'=>'community.communities',
        'plans'=>'community.plans',
        'work'=>'community.work-orders',
        'incidents'=>'community.incidents',
        'tickets'=>'community.tickets',
        'appointments'=>'community.appointments',
        'shifts'=>'community.shifts',
        'attendance'=>'community.attendance',
        'expenses'=>'community.expenses',
        'notices'=>'community.notices',
    ];

    private const LEGACY = [
        'owner'=>['properties','documents','appointments','tickets','incidents','fees'],
        'employee'=>['plans','communities','work','incidents','shifts','attendance','appointments','documents','tickets','expenses'],
    ];

    public function enabledSections(string $role): array
    {
        $panel = NovaPanel::query()->where('key','community')->first();

        if (! $panel || ! NovaBinding::query()
            ->where('panel_id',$panel->id)
            ->where('role',$role)
            ->where('representation',NovaRepresentationType::Livewire)
            ->exists()) {
            return self::LEGACY[$role] ?? [];
        }

        return collect(self::MAP)
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
        return $section === 'home' || in_array($section, $this->enabledSections($role), true);
    }
}
