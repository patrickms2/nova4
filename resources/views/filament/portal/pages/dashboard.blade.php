<x-filament-panels::page class="mobile-portal-embedded">
    @if (in_array(\App\Support\CommunityPortalContext::portalType(), ['owner', 'employee'], true))
        @livewire('community-portal', ['embedded' => true])
    @else
        @livewire('mobile-portal', ['embedded' => true])
    @endif
</x-filament-panels::page>
