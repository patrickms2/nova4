<x-filament-panels::page>
    <div class="portal-support-page">
        <div class="flex items-center justify-end gap-2 mb-2">
            <x-employee-help-popup page="portal-support" />
        </div>

        {{ $this->table }}
    </div>
</x-filament-panels::page>
