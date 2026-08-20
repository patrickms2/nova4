<x-filament-panels::page class="casa-el-patio-dashboard" x-data="{ tab: 'resumen' }">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <nav class="flex gap-1 p-1 bg-gray-100 dark:bg-gray-800 rounded-lg" aria-label="Dashboard tabs">
            @foreach (['resumen' => 'Resumen', 'financiero' => 'Financiero', 'ocupacion' => 'Ocupación', 'operacion' => 'Operación'] as $key => $label)
                <button type="button"
                        x-on:click="tab = '{{ $key }}'"
                        class="px-4 py-2 text-sm font-medium rounded-md transition-colors"
                        :class="tab === '{{ $key }}' ? 'bg-white dark:bg-gray-700 text-primary-600 dark:text-primary-400 shadow' : 'text-gray-600 dark:text-gray-300 hover:text-gray-900 dark:hover:text-white'">
                    {{ $label }}
                </button>
            @endforeach
        </nav>

        <a href="{{ \App\Filament\Pages\RentalContractSimulator::getUrl() }}" class="text-sm font-medium text-primary-600 dark:text-primary-400 hover:underline">
            Abrir simulador de contrato →
        </a>
    </div>

    <div x-show="tab === 'resumen'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Facturación del mes</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['billing'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Neto propietario</div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['netOwner'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Ocupación</div>
                <div class="text-2xl font-semibold tracking-tight">{{ $kpis['occupancy'] }}%</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Cash flow gestor</div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['cashFlow'], 2, ',', '.') }}</div>
            </x-filament::card>
        </div>

        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <x-filament::card class="lg:col-span-2">
                <h3 class="text-base font-semibold">Últimas reservas</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($latestReservations as $reservation)
                        <div class="flex items-center justify-between p-3 border border-gray-100 rounded-xl dark:border-gray-800">
                            <div>
                                <div class="font-medium">{{ $reservation['guest'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $reservation['property'] }} · {{ $reservation['check_in'] }} - {{ $reservation['check_out'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="font-semibold">€{{ number_format($reservation['amount'], 2, ',', '.') }}</div>
                                <div class="text-xs text-gray-500 uppercase">{{ $reservation['channel'] }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">Aún no hay reservas registradas.</div>
                    @endforelse
                </div>
            </x-filament::card>

            <x-filament::card>
                <h3 class="text-base font-semibold">Timeline</h3>
                <div class="mt-4 space-y-3">
                    @forelse ($timeline as $event)
                        <div class="text-sm">
                            <div class="font-medium">{{ $event['title'] }}</div>
                            <div class="text-gray-500 dark:text-gray-400">{{ $event['time'] }}</div>
                        </div>
                    @empty
                        <div class="text-sm text-gray-500 dark:text-gray-400">Sin actividad reciente.</div>
                    @endforelse
                </div>
            </x-filament::card>
        </div>
    </div>

    <div x-show="tab === 'financiero'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Facturación del mes</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['billing'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Facturación alojamiento</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['accommodation'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Servicios</div>
                <div class="text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($kpis['services'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Gastos operativos</div>
                <div class="text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($kpis['operatingExpenses'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Neto propietario</div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['netOwner'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Cash flow gestor</div>
                <div class="text-2xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">€{{ number_format($kpis['cashFlow'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Real payout</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['realPayout'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Diferencia</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['difference'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Comisión Airbnb/Booking</div>
                <div class="text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($kpis['channelCommission'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Comisión Bayside</div>
                <div class="text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($kpis['managementCommission'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Comisiones totales</div>
                <div class="text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($kpis['totalCommissions'], 2, ',', '.') }}</div>
            </x-filament::card>
        </div>
    </div>

    <div x-show="tab === 'ocupacion'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Ocupación</div>
                <div class="text-2xl font-semibold tracking-tight">{{ $kpis['occupancy'] }}%</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">ADR</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['adr'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">RevPAR</div>
                <div class="text-2xl font-semibold tracking-tight">€{{ number_format($kpis['revpar'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Próxima llegada</div>
                <div class="text-lg font-semibold tracking-tight">
                    {{ $kpis['nextArrival']['guest'] ?? '—' }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $kpis['nextArrival']['date'] ?? '' }}</span>
                </div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Próxima salida</div>
                <div class="text-lg font-semibold tracking-tight">
                    {{ $kpis['nextDeparture']['guest'] ?? '—' }}
                    <span class="text-sm font-normal text-gray-500 dark:text-gray-400">{{ $kpis['nextDeparture']['date'] ?? '' }}</span>
                </div>
            </x-filament::card>
        </div>
    </div>

    <div x-show="tab === 'operacion'" x-cloak class="space-y-6">
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Cobros pendientes</div>
                <div class="text-2xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">€{{ number_format($kpis['pendingPayments'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Gastos pendientes</div>
                <div class="text-2xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">€{{ number_format($kpis['pendingExpenses'], 2, ',', '.') }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Incidencias abiertas</div>
                <div class="text-2xl font-semibold tracking-tight">{{ $kpis['openIncidents'] }}</div>
            </x-filament::card>

            <x-filament::card>
                <div class="text-sm text-gray-500 dark:text-gray-400">Valor del contenido</div>
                <div class="text-2xl font-semibold tracking-tight text-blue-600 dark:text-blue-400">€{{ number_format($kpis['inventoryValue'], 2, ',', '.') }}</div>
            </x-filament::card>
        </div>
    </div>
</x-filament-panels::page>
