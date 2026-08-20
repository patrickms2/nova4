@if ($isSimple)
    <x-filament::dropdown teleport placement="bottom-end">
        <x-slot name="trigger">
            <button type="button"
                class="fi-icon-btn fi-size-md fi-topbar-database-notifications-btn fi-bg-primary-600 hover:fi-bg-primary-700 focus:fi-ring-primary-500 fi-text-white fi-flex fi-items-center fi-gap-2">
                <span class="w-5 h-5 font-semibold bg-white rounded-full shrink-0 text-primary-500">
                    {{str($labels[$currentPanel->getId()] ?? $currentPanel->getId())->substr(0, 1)->upper()}}
                </span>
                <span class="text-white">
                    {{ $labels[$currentPanel->getId()] ?? str($currentPanel->getId())->ucfirst() }}
                </span>

                <x-filament::icon
                    icon="heroicon-m-chevron-down"
                    icon-alias="panels::panel-switch-simple-icon"
                    class="w-5 h-5 text-white ms-auto shrink-0"
                />

            </button>
        </x-slot>

        <x-filament::dropdown.list>
            @foreach ($panels as $id => $url)
                <x-filament::dropdown.list.item
                    :href="$url"
                    :badge="str($labels[$id] ?? $id)->substr(0, 2)->upper()"
                    tag="a"
                >
                {{ $labels[$id] ?? str($id)->ucfirst() }}
                </x-filament::dropdown.list.item>
            @endforeach
        </x-filament::dropdown.list>

    </x-filament::dropdown>
@else
    <style>
        .panel-switch-modal .fi-modal-content {
            align-items: center !important;
            justify-content: center !important;
        }
        .panel-switch-modal .fi-modal {
    right: auto !important;
    left: 0 !important;
    transform: translateX(-100%) !important;
}

.panel-switch-modal.fi-modal-open .fi-modal {
    transform: translateX(0%) !important;
}

    </style>
@php
    $currentPanelLabel = $labels[$currentPanel->getId()] ?? str($currentPanel->getId())->ucfirst();
@endphp

<x-filament::icon-button
    x-data="{}"
    icon="heroicon-o-shield-check"
    icon-alias="panels::panel-switch-modern-icon"
    icon-size="lg"
    @click="$dispatch('open-modal', { id: 'panel-switch' })"
    :label="$heading"
   class="shrink-0 grow-0 text-gray-500 hover:text-gray-700 dark:text-gray-200 dark:hover:text-gray-400"
/>

<x-filament::modal
    id="panel-switch"
    width="md"
    alignment="start"
    slide-over
    sticky-header
    :heading="$heading"
    teleport="body"
    display-classes="block"
    class="panel-switch-modal"
>
    <div
        class="panel-switch-grid"
    >
        @foreach ($panels as $id => $url)
            <a
                href="{{ $url }}"
                class="flex flex-col items-center justify-start flex-1 hover:cursor-pointer group panel-switch-card"
            >
                <div
                    @class([
                        'p-2 bg-white rounded-lg shadow-md dark:bg-gray-800 panel-switch-card-section',
                        'group-hover:ring-2 group-hover:ring-primary-600' => $id !== $currentPanel->getId(),
                        'ring-2 ring-primary-600' => $id === $currentPanel->getId(),
                    ])
                >
                    @if ($renderIconAsImage)
                        <img
                            class="rounded-lg panel-switch-card-image w-16 h-16 object-cover"
                            src="{{ $icons[$id] ?? 'https://raw.githubusercontent.com/bezhanSalleh/filament-panel-switch/3.x/art/banner.jpg' }}"
                            alt="Panel Image"
                        >
                    @else
                        @php
                            $iconName = $icons[$id] ?? 'heroicon-s-square-2-stack';
                        @endphp
                        @svg($iconName, 'text-primary-600 panel-switch-card-icon w-12 h-12')
                    @endif
                </div>
                <span
                    @class([
                        'mt-2 text-sm font-medium text-center text-gray-400 dark:text-gray-200 break-words panel-switch-card-title',
                        'text-gray-400 dark:text-gray-200 group-hover:text-primary-600 group-hover:dark:text-primary-400' => $id !== $currentPanel->getId(),
                        'text-primary-600 dark:text-primary-400' => $id === $currentPanel->getId(),
                    ])
                >
                    {{ $labels[$id] ?? str($id)->ucfirst() }}
                </span>
            </a>
        @endforeach
    </div>
</x-filament::modal>
@endif
