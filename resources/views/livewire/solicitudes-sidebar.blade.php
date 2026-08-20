<div wire:poll.10s="loadSidebar" class="flex h-full flex-col">
    {{-- Header --}}
    <div class="mb-3 flex items-center justify-between">
        <h3 class="text-sm font-bold text-gray-900 dark:text-white">
            Últimos Servicios
        </h3>
        <div class="flex items-center gap-2">
            <span class="text-[10px] font-medium text-gray-400">{{ count($servicios) }} resultados</span>
            @if($loading)
                <x-filament::loading-indicator class="h-4 w-4 text-blue-500" />
            @else
                <div class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></div>
            @endif
        </div>
    </div>

    {{-- Filtro pills --}}
    <div class="mb-3 flex flex-wrap gap-1">
        {{-- TODOS --}}
        <button
            wire:click="setFiltro('')"
            @class([
                'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                'bg-blue-600 text-white shadow-md ring-2 ring-blue-300/50 scale-105 dark:bg-blue-500 dark:ring-blue-400/40' => $filtroEstado === '',
                'bg-gray-100 text-gray-500 hover:bg-gray-200 hover:scale-105 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $filtroEstado !== '',
            ])
        >TODOS</button>

        {{-- PEND --}}
        <button
            wire:click="setFiltro('1')"
            @class([
                'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                'bg-amber-500 text-white shadow-md ring-2 ring-amber-300/50 scale-105 dark:bg-amber-500 dark:ring-amber-400/40' => $filtroEstado === '1',
                'bg-gray-100 text-gray-500 hover:bg-gray-200 hover:scale-105 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $filtroEstado !== '1',
            ])
        >PEND</button>

        {{-- TRAM --}}
        <button
            wire:click="setFiltro('2')"
            @class([
                'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                'bg-emerald-500 text-white shadow-md ring-2 ring-emerald-300/50 scale-105 dark:bg-emerald-500 dark:ring-emerald-400/40' => $filtroEstado === '2',
                'bg-gray-100 text-gray-500 hover:bg-gray-200 hover:scale-105 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $filtroEstado !== '2',
            ])
        >TRAM</button>

        {{-- CANC --}}
        <button
            wire:click="setFiltro('3')"
            @class([
                'rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider transition-all duration-200',
                'bg-red-500 text-white shadow-md ring-2 ring-red-300/50 scale-105 dark:bg-red-500 dark:ring-red-400/40' => $filtroEstado === '3',
                'bg-gray-100 text-gray-500 hover:bg-gray-200 hover:scale-105 dark:bg-gray-800 dark:text-gray-400 dark:hover:bg-gray-700' => $filtroEstado !== '3',
            ])
        >CANC</button>
    </div>

    {{-- Lista de servicios con animaciones --}}
    <div class="flex-1 space-y-1.5 overflow-y-auto scroll-smooth" style="max-height: 560px;">
        @forelse($servicios as $index => $servicio)
            @php
                $estado = $servicio['nombreEstado'] ?? '';
                $colorBorder = match($estado) {
                    'SOLICITADO' => 'border-l-amber-400',
                    'TRAMITADO' => 'border-l-emerald-400',
                    'CANCELADO' => 'border-l-red-400',
                    'RESERVADO' => 'border-l-blue-400',
                    'NO ATENDIDO' => 'border-l-orange-400',
                    default => 'border-l-gray-400',
                };
                $colorGlow = match($estado) {
                    'SOLICITADO' => 'hover:shadow-amber-500/10',
                    'TRAMITADO' => 'hover:shadow-emerald-500/10',
                    'CANCELADO' => 'hover:shadow-red-500/10',
                    'RESERVADO' => 'hover:shadow-blue-500/10',
                    'NO ATENDIDO' => 'hover:shadow-orange-500/10',
                    default => 'hover:shadow-gray-500/10',
                };
                $animDelay = min($index * 40, 400);
            @endphp
            <div
                class="group rounded-lg border-l-[3px] {{ $colorBorder }} {{ $colorGlow }} bg-white p-2.5 shadow-sm ring-1 ring-gray-950/5 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-md dark:bg-gray-800/80 dark:ring-white/10"
                style="animation: sidebarSlideIn 0.35s cubic-bezier(0.22, 1, 0.36, 1) {{ $animDelay }}ms both;"
            >
                {{-- Fecha + código --}}
                <div class="mb-1 flex items-center justify-between">
                    <span class="text-[10px] font-medium text-gray-400 transition-colors group-hover:text-gray-600 dark:group-hover:text-gray-300">
                        {{ \Illuminate\Support\Str::limit($servicio['fecha_servicio'] ?? '', 16) }}
                    </span>
                    <span class="rounded bg-gray-100 px-1 py-px text-[9px] font-mono text-gray-400 dark:bg-gray-700">
                        {{ $servicio['codservicio'] ?? '' }}
                    </span>
                </div>

                {{-- Hotel --}}
                <div class="text-xs font-bold text-gray-900 transition-colors group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                    {{ $servicio['nombreUsuario'] ?? '-' }}
                </div>

                {{-- Detalles --}}
                <div class="mt-0.5 flex items-center gap-1.5 text-[11px] text-gray-500 dark:text-gray-400">
                    <span>{{ strtoupper($servicio['nombreTipo'] ?? 'NORMAL') }}</span>
                    <span class="text-gray-300 dark:text-gray-600">·</span>
                    <span>{{ $servicio['personas'] ?? '?' }}pax</span>
                    @if(!empty($servicio['habitacion']))
                        <span class="text-gray-300 dark:text-gray-600">·</span>
                        <span>Hab {{ $servicio['habitacion'] }}</span>
                    @endif
                </div>

                {{-- Cliente --}}
                @if(!empty($servicio['nombre']))
                    <div class="mt-0.5 truncate text-[11px] text-gray-400 dark:text-gray-500">
                        {{ $servicio['nombre'] }}
                    </div>
                @endif

                {{-- Estado + Municipio --}}
                <div class="mt-1.5 flex items-center justify-between">
                    @php
                        $badgeIcon = match($estado) {
                            'SOLICITADO' => '⏳',
                            'TRAMITADO' => '✅',
                            'CANCELADO' => '❌',
                            'RESERVADO' => '📋',
                            'NO ATENDIDO' => '⚠️',
                            default => '●',
                        };
                    @endphp
                    <span @class([
                        'inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide',
                        'bg-amber-50 text-amber-700 ring-1 ring-amber-200/60 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-500/30' => $estado === 'SOLICITADO',
                        'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200/60 dark:bg-emerald-900/20 dark:text-emerald-400 dark:ring-emerald-500/30' => $estado === 'TRAMITADO',
                        'bg-red-50 text-red-700 ring-1 ring-red-200/60 dark:bg-red-900/20 dark:text-red-400 dark:ring-red-500/30' => $estado === 'CANCELADO',
                        'bg-gray-50 text-gray-600 ring-1 ring-gray-200/60 dark:bg-gray-700 dark:text-gray-400' => !in_array($estado, ['SOLICITADO', 'TRAMITADO', 'CANCELADO']),
                    ])>
                        <span>{{ $badgeIcon }}</span>
                        {{ $estado }}
                    </span>
                    <span class="rounded-full bg-gray-50 px-1.5 py-px text-[9px] font-medium text-gray-400 dark:bg-gray-700 dark:text-gray-500">
                        {{ $servicio['nombreMunicipio'] ?? '' }}
                    </span>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center gap-2 py-12 text-gray-400">
                <x-filament::icon icon="heroicon-o-inbox" class="h-8 w-8 opacity-40" />
                <span class="text-xs">Sin servicios</span>
            </div>
        @endforelse
    </div>

    @once
    <style>
        @keyframes sidebarSlideIn {
            from {
                opacity: 0;
                transform: translateX(20px) scale(0.95);
            }
            to {
                opacity: 1;
                transform: translateX(0) scale(1);
            }
        }
    </style>
    @endonce
</div>
