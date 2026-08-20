{{-- Tab de Turnos --}}
<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <h2 class="text-lg font-bold text-white/90">Mis Turnos</h2>
        <div class="text-sm text-white/60">
            {{ \Illuminate\Support\Carbon::now()->translatedFormat('F Y') }} · Horario rotativo
        </div>
    </div>

    {{-- Month summary pills --}}
    <div class="tl-s1 flex items-center justify-between gap-2 p-4">
        <div class="flex flex-wrap gap-2">
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: #3b82f620; color: #3b82f6;">M: {{ $stats['turnos_m'] ?? 0 }}</span>
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: #f59e0b20; color: #f59e0b;">P: {{ $stats['turnos_p'] ?? 0 }}</span>
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: #8b5cf620; color: #8b5cf6;">N: {{ $stats['turnos_n'] ?? 0 }}</span>
            <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-semibold" style="background-color: #22c55e20; color: #22c55e;">L: {{ $stats['turnos_l'] ?? 0 }}</span>
        </div>
        <span class="rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-white/70">Total: {{ $stats['turnos_mes'] ?? 0 }}</span>
    </div>

    {{-- Next shift info --}}
    <div class="tl-s1 p-4">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-white/80">Próximo turno:</p>
                <p class="text-xs text-white/60 mt-1">{{ $stats['proximo_turno'] ?? 'Sin turnos próximos' }}</p>
            </div>
            <x-portal.button variant="primary" size="sm" as="a" href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}">
                Ver calendario completo
            </x-portal.button>
        </div>
    </div>

    {{-- Quick summary --}}
    @if(($stats['turnos_mes'] ?? 0) > 0)
        <div class="tl-s1 p-4">
            <h3 class="text-sm font-semibold text-white/80 mb-3">Resumen del mes</h3>
            <div class="grid grid-cols-2 gap-3">
                <div class="text-center p-3 bg-white/5 rounded-lg">
                    <p class="text-2xl font-bold text-white/90">{{ $stats['turnos_mes'] ?? 0 }}</p>
                    <p class="text-xs text-white/60">Turnos totales</p>
                </div>
                <div class="text-center p-3 bg-white/5 rounded-lg">
                    <p class="text-2xl font-bold text-emerald-400">{{ $stats['turnos_l'] ?? 0 }}</p>
                    <p class="text-xs text-white/60">Días libres</p>
                </div>
            </div>
        </div>
    @else
        <div class="tl-s1 p-6 text-center">
            <p class="text-white/60">Sin turnos próximos</p>
            <x-portal.button variant="ghost" size="sm" as="a" href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}" class="mt-3">
                Ver calendario completo
            </x-portal.button>
        </div>
    @endif

    {{-- Link to full calendar --}}
    <div class="tl-s1 p-4 text-center">
        <p class="text-sm text-white/60 mb-3">Para ver el calendario interactivo con todos los detalles:</p>
        <x-portal.button variant="primary" as="a" href="{{ \App\Filament\Portal\Pages\TaxistaPortal::getUrl(['tab' => 'turnos'], panel: 'portal') }}">
            Abrir Calendario de Turnos
        </x-portal.button>
    </div>
</div>
