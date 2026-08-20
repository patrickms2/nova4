<x-filament-panels::page>
    <div class="portal-appointments-page">
        <div class="flex items-center justify-end mb-2">
            <x-employee-help-popup page="portal-appointments" />
        </div>

        {{$this->table}}
    </div>
</x-filament-panels::page>
