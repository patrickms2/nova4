@props([
    'resetTableSearch' => false,
    'removeTableFilter' => null,
])

<div>
    @if ($resetTableSearch)
        <x-filament::button
            color="danger"
            wire:click="resetTableSearch"
            x-on:click="close"
        >
            {{ __('filament-tables::table.filters.actions.reset.label') }}
        </x-filament::button>
    @else
        <x-filament::button
            color="danger"
            wire:click="removeTableFilter({{ $removeTableFilter }})"
            x-on:click="close"
        >
            {{ __('filament-tables::table.filters.actions.reset.label') }}
        </x-filament::button>
    @endif
</div>
