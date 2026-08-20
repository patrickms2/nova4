<x-filament-panels::page>
    <div class="space-y-5" x-data="{ activeTab: 'servicios' }">

        {{-- ═══════════════════════════════════════════════════════════════
             SECTION 1: STATS BAR — HOY / MES por municipio
        ═══════════════════════════════════════════════════════════════ --}}
        @livewire('solicitudes-stats-bar')

        {{-- ═══════════════════════════════════════════════════════════════
             SECTION 2: MAPA + SIDEBAR
        ═══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 gap-4 xl:grid-cols-4">
            {{-- Mapa (3/4) — uses filament-google-maps MapWidget --}}
            <div class="xl:col-span-3">
                @livewire(\App\Filament\App\Widgets\SolicitudesMapWidget::class)
            </div>

            {{-- Sidebar últimos servicios (1/3) --}}
            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                @livewire('solicitudes-sidebar')
            </div>
        </div>

        {{-- ═══════════════════════════════════════════════════════════════
             SECTION 3: TABS — Servicios / Gráficas / Estado Hoteles
        ═══════════════════════════════════════════════════════════════ --}}
        <div>
            {{-- Tab navigation --}}
            <div class="mb-4 flex flex-wrap gap-1 rounded-xl bg-gray-100 p-1 dark:bg-gray-800">
                @foreach([
                    ['servicios', 'Tabla Servicios', 'heroicon-m-table-cells'],
                    ['graficas', 'Gráficas', 'heroicon-m-chart-bar'],
                    ['hoteles', 'Estado Hoteles', 'heroicon-m-building-office-2'],
                ] as [$tab, $label, $icon])
                    <button
                        @click="activeTab = '{{ $tab }}'"
                        :class="activeTab === '{{ $tab }}'
                            ? 'bg-white text-primary-600 shadow-sm dark:bg-gray-900 dark:text-primary-400'
                            : 'text-gray-600 hover:text-gray-900 dark:text-gray-400 dark:hover:text-gray-200'"
                        class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold transition"
                    >
                        <x-filament::icon :icon="$icon" class="h-4 w-4"/>
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            {{-- Tab: Tabla Servicios --}}
            <div x-show="activeTab === 'servicios'" x-cloak>
                @livewire('servicios-live-dashboard')
            </div>

            {{-- Tab: Gráficas --}}
            <div x-show="activeTab === 'graficas'" x-cloak>
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <div class="md:col-span-2 xl:col-span-3">
                        @livewire(\App\Filament\App\Widgets\SolicitudesHotelChart::class)
                    </div>
                    @livewire(\App\Filament\App\Resources\SolicitudesEstadoChart::class)
                    @livewire(\App\Filament\App\Widgets\SolicitudesHoraChart::class)
                    @livewire(\App\Filament\App\Widgets\SolicitudesMunicipioChart::class)
                </div>
            </div>

            {{-- Tab: Estado Hoteles --}}
            <div x-show="activeTab === 'hoteles'" x-cloak>
                <div
                    class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    @livewire('hoteles-presencia')
                </div>
            </div>
        </div>

    </div>
</x-filament-panels::page>
