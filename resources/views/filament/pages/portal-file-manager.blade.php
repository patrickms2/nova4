<x-filament-panels::page>
    <form wire:submit="executeOperation">
        {{ $this->form }}
        
        <div class="mt-6">
            {!! $this->renderActions() !!}
        </div>
    </form>
</x-filament-panels::page>
