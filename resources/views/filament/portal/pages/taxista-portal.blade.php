<x-filament-panels::page>
    <div class="space-y-6 md:space-y-8">
        @php
            $activeTab = request()->get('tab', 'dashboard');
            // Simple check - if accessing turnos tab, assume user has permission
            $isEmployeePortal = $activeTab === 'turnos';
        @endphp
        
        @if($activeTab === 'turnos')
            @livewire('employee-shift-calendar')
        @else
            @livewire('portal-taxista-pro', ['embedded' => true])
        @endif
    </div>
</x-filament-panels::page>
