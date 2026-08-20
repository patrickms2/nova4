<x-filament::page>
    {{ $this->form }}
    <x-filament::button wire:click="submit" class="mt-4">
        submit
    </x-filament::button>
</x-filament::page>