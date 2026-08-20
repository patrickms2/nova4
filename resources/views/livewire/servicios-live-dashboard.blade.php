<div wire:poll.5s="loadServicios" class="space-y-6">

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-4">
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Solicitudes Pendientes</div>
            <div class="mt-1 text-3xl font-bold text-warning-600 dark:text-warning-400">{{ number_format($totalPend) }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Total Solicitudes</div>
            <div class="mt-1 text-3xl font-bold text-primary-600 dark:text-primary-400">{{ number_format($totalTotal) }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Página</div>
            <div class="mt-1 text-3xl font-bold text-gray-700 dark:text-gray-200">{{ $page }} / {{ $this->totalPages }}</div>
        </div>
        <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Última actualización</div>
                    <div class="mt-1 text-xl font-bold text-gray-700 dark:text-gray-200">{{ $lastUpdated }}</div>
                </div>
                @if($loading)
                    <x-filament::loading-indicator class="h-6 w-6 text-blue-500" />
                @else
                    <div class="h-3 w-3 rounded-full bg-emerald-500 animate-pulse" title="En vivo"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Filter Buttons --}}
    <div class="flex flex-wrap items-center gap-2 mb-4">
        <span class="text-sm font-medium text-gray-500 dark:text-gray-400 mr-2">Filtrar:</span>

        {{-- Filtro por Hotel --}}
        <select 
            wire:model.live="filtroHotel" 
            class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-blue-500 dark:focus:ring-blue-500"
        >
            <option value="">Todos los hoteles</option>
            @foreach($hoteles as $hotel)
                <option value="{{ $hotel['id'] }}">{{ $hotel['nombre'] }}</option>
            @endforeach
        </select>

        {{-- Filtro por Fecha Desde --}}
        <input 
            type="date" 
            wire:model.live="fechaDesde"
            class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-blue-500 dark:focus:ring-blue-500"
            placeholder="Fecha desde"
        />

        {{-- Filtro por Fecha Hasta --}}
        <input 
            type="date" 
            wire:model.live="fechaHasta"
            class="rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:focus:border-blue-500 dark:focus:ring-blue-500"
            placeholder="Fecha hasta"
        />

        {{-- Limpiar Filtros --}}
        <button 
            wire:click="limpiarFiltros"
            class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700"
        >
            <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
            Limpiar
        </button>
    </div>

    {{-- Filter Buttons - Estados --}}
    <div class="flex flex-wrap items-center gap-2">

        {{-- TODOS --}}
        <button
            wire:click="setFiltroEstado('')"
            @class([
                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-200 shadow-sm',
                'bg-blue-600 text-white ring-2 ring-blue-300 dark:bg-blue-500 dark:ring-blue-400/50' => $filtroEstado === '',
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filtroEstado !== '',
            ])
        >TODOS</button>

        {{-- SOLICITADO --}}
        <button
            wire:click="setFiltroEstado('1')"
            @class([
                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-200 shadow-sm',
                'bg-amber-500 text-white ring-2 ring-amber-300 dark:bg-amber-500 dark:ring-amber-400/50' => $filtroEstado === '1',
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filtroEstado !== '1',
            ])
        >SOLICITADO</button>

        {{-- TRAMITADO --}}
        <button
            wire:click="setFiltroEstado('2')"
            @class([
                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-200 shadow-sm',
                'bg-emerald-500 text-white ring-2 ring-emerald-300 dark:bg-emerald-500 dark:ring-emerald-400/50' => $filtroEstado === '2',
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filtroEstado !== '2',
            ])
        >TRAMITADO</button>

        {{-- CANCELADO --}}
        <button
            wire:click="setFiltroEstado('3')"
            @class([
                'inline-flex items-center rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-200 shadow-sm',
                'bg-red-500 text-white ring-2 ring-red-300 dark:bg-red-500 dark:ring-red-400/50' => $filtroEstado === '3',
                'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700' => $filtroEstado !== '3',
            ])
        >CANCELADO</button>

        <div class="ml-auto">
            <button wire:click="refresh" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-100 px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                <x-filament::icon icon="heroicon-m-arrow-path" class="h-4 w-4" />
                Refrescar
            </button>
        </div>
    </div>

    {{-- Servicios Table --}}
    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="overflow-x-auto">
            <table class="w-full table-auto divide-y divide-gray-200 dark:divide-white/5">
                <thead>
                    <tr class="bg-gray-50 dark:bg-white/5">
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Fecha</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Hotel</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Cliente</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Hab.</th>
                        <th class="px-4 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">PAX</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Tipo</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Municipio</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-400">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-white/5">
                    @forelse($servicios as $servicio)
                        @php
                            $estado = $servicio['nombreEstado'] ?? '';
                            $color = match($estado) {
                                'SOLICITADO' => 'warning',
                                'TRAMITADO' => 'success',
                                'CANCELADO' => 'danger',
                                'RESERVADO' => 'info',
                                'NO ATENDIDO' => 'warning',
                                default => 'gray',
                            };
                        @endphp
                        <tr class="transition hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ \Illuminate\Support\Str::limit($servicio['fecha_servicio'] ?? '', 16) }}
                            </td>
                            <td class="px-4 py-3 text-sm font-medium text-gray-900 dark:text-white">
                                {{ $servicio['nombreUsuario'] ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $servicio['nombre'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                {{ $servicio['habitacion'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-center text-sm text-gray-700 dark:text-gray-300">
                                {{ $servicio['personas'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $servicio['nombreTipo'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                {{ $servicio['nombreMunicipio'] ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                <span @class([
                                    'inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset',
                                    'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-400/10 dark:text-amber-400 dark:ring-amber-400/30' => $color === 'warning',
                                    'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-400/10 dark:text-emerald-400 dark:ring-emerald-400/30' => $color === 'success',
                                    'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30' => $color === 'danger',
                                    'bg-blue-50 text-blue-700 ring-blue-600/20 dark:bg-blue-400/10 dark:text-blue-400 dark:ring-blue-400/30' => $color === 'info',
                                    'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/30' => $color === 'gray',
                                ])>
                                    {{ $estado }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                No hay solicitudes disponibles.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="flex items-center justify-between border-t border-gray-200 px-4 py-3 dark:border-white/10">
            <button
                wire:click="previousPage"
                @disabled($page <= 1)
                @class([
                    'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' => $page > 1,
                    'cursor-not-allowed bg-gray-50 text-gray-400 dark:bg-gray-900 dark:text-gray-600' => $page <= 1,
                ])
            >
                <x-filament::icon icon="heroicon-m-chevron-left" class="h-4 w-4" />
                Anterior
            </button>

            <span class="text-sm text-gray-500 dark:text-gray-400">
                Mostrando {{ count($servicios) }} de {{ number_format($totalTotal) }} solicitudes
            </span>

            <button
                wire:click="nextPage"
                @disabled($page >= $this->totalPages)
                @class([
                    'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-sm font-medium transition',
                    'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300' => $page < $this->totalPages,
                    'cursor-not-allowed bg-gray-50 text-gray-400 dark:bg-gray-900 dark:text-gray-600' => $page >= $this->totalPages,
                ])
            >
                Siguiente
                <x-filament::icon icon="heroicon-m-chevron-right" class="h-4 w-4" />
            </button>
        </div>
    </div>
</div>
