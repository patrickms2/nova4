<div wire:poll.10s="loadPresencia" class="space-y-4">

    {{-- Header with stats --}}
    <div class="flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <span class="relative flex h-3 w-3">
                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                <span class="relative inline-flex h-3 w-3 rounded-full bg-success-500"></span>
            </span>
            <span class="text-sm font-semibold text-success-600 dark:text-success-400">{{ $totalActivos }} activos</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex h-3 w-3 rounded-full bg-warning-500"></span>
            <span class="text-sm font-semibold text-warning-600 dark:text-warning-400">{{ $totalRecientes }} recientes</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex h-3 w-3 rounded-full bg-gray-300 dark:bg-gray-600"></span>
            <span class="text-sm text-gray-500 dark:text-gray-400">{{ $totalInactivos }} inactivos</span>
        </div>

        <div class="ml-auto flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
            @if($loading)
                <x-filament::loading-indicator class="h-4 w-4 text-primary-500" />
                <span>Actualizando...</span>
            @else
                <span>Actualizado: {{ $lastUpdated }}</span>
            @endif
        </div>
    </div>

    {{-- Hotel Grid --}}
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6">
        @foreach($hoteles as $hotel)
            @php
                $estado = $hotel['estado'];
            @endphp
            <div @class([
                'group relative flex flex-col items-center rounded-lg p-3 text-center transition-all duration-300',
                'bg-success-50 ring-2 ring-success-500/40 shadow-sm shadow-success-500/10 dark:bg-success-500/10 dark:ring-success-400/30' => $estado === 'activo',
                'bg-warning-50 ring-1 ring-warning-400/30 dark:bg-warning-500/10 dark:ring-warning-400/20' => $estado === 'reciente',
                'bg-gray-50 ring-1 ring-gray-200 dark:bg-gray-800/50 dark:ring-white/5' => $estado === 'inactivo',
            ])>
                {{-- Status indicator --}}
                <div class="mb-2">
                    @if($estado === 'activo')
                        <span class="relative flex h-4 w-4">
                            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-success-400 opacity-75"></span>
                            <span class="relative inline-flex h-4 w-4 rounded-full bg-success-500"></span>
                        </span>
                    @elseif($estado === 'reciente')
                        <span class="inline-flex h-4 w-4 rounded-full bg-warning-500"></span>
                    @else
                        <span class="inline-flex h-4 w-4 rounded-full bg-gray-300 dark:bg-gray-600"></span>
                    @endif
                </div>

                {{-- Hotel name --}}
                <span @class([
                    'text-xs font-semibold leading-tight line-clamp-2',
                    'text-success-800 dark:text-success-300' => $estado === 'activo',
                    'text-warning-800 dark:text-warning-300' => $estado === 'reciente',
                    'text-gray-500 dark:text-gray-500' => $estado === 'inactivo',
                ])>
                    {{ $hotel['nombre'] }}
                </span>

                {{-- Activity info --}}
                @if($hotel['solicitudes'] > 0)
                    <span @class([
                        'mt-1 text-[10px] font-medium',
                        'text-success-600 dark:text-success-400' => $estado === 'activo',
                        'text-warning-600 dark:text-warning-400' => $estado === 'reciente',
                        'text-gray-400 dark:text-gray-600' => $estado === 'inactivo',
                    ])>
                        {{ $hotel['solicitudes'] }} solicitudes
                    </span>
                @endif

                {{-- Tooltip on hover --}}
                @if($hotel['ultima'])
                    <div class="pointer-events-none absolute -top-8 left-1/2 z-10 -translate-x-1/2 whitespace-nowrap rounded bg-gray-900 px-2 py-1 text-[10px] text-white opacity-0 shadow-lg transition-opacity group-hover:opacity-100 dark:bg-gray-700">
                        Última: {{ $hotel['ultima'] }}
                        @if($hotel['municipio'])
                            · {{ $hotel['municipio'] }}
                        @endif
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if(empty($hoteles))
        <div class="py-12 text-center text-sm text-gray-500 dark:text-gray-400">
            No se pudieron cargar los hoteles.
        </div>
    @endif
</div>
