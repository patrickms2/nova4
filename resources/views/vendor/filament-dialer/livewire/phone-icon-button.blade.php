@php
$isSidebarCollapsibleOnDesktop = filament()->isSidebarCollapsibleOnDesktop();
$anderia = \Andreia\FilamentUiSwitcher\Support\UiPreferenceManager::get('ui.layout', 'sidebar');
@endphp
<div class="fi-no-database">
<div>
<div class="fi-modal-trigger">
<button
    @if($anderia === 'sidebar-no-topbar')
    class="fi-sidebar-database-notifications-btn mr-2"
    x-show="$store.sidebar.isOpen"
    @else
    class="fi-icon-btn fi-size-md fi-topbar-database-notifications-btn ml-1 mr-2"
    @endif
    tooltip="Phone Dialer"
    x-on:click.prevent="$dispatch('open-modal', { id: 'phone-dialer-sidebar' })"
    wire:key="phone-icon-button"
    action="open-modal"
>

        <x-filament::icon
            icon="heroicon-o-phone-arrow-up-right"
            class="fi-icon fi-size-md"
        />

    @if($anderia === 'sidebar-no-topbar')
        <span

                x-show="$store.sidebar.isOpen"
                x-transition:enter="fi-transition-enter"
                x-transition:enter-start="fi-transition-enter-start"
                x-transition:enter-end="fi-transition-enter-end"

            class="fi-sidebar-database-notifications-btn-label hidden"
        >

        </span>
     @endif
</button>
 </div>
</div>
</div>
