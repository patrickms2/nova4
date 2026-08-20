<div class="min-h-full  text-white p-4 md:p-6">

    <div class="mb-8">
        <h1 class="text-2xl font-bold  text-[#666666]">PANEL ADMINISTRADOR</h1>
        <p class="text-sm text-[#666666]">Control total desde un solo lugar.</p>
    </div>

    {{-- Stats --}}
    <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
        @foreach ([
            ['Comunidades', $stats['communities'], '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/></svg>'],
            ['Empleados', $employeesCount, '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>'],
            ['Servicios hoy', $servicesToday, '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>'],
            ['Incidencias', $openIncidentsCount, '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>'],
        ] as $card)
            <div class="p-5 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A]">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 rounded-lg bg-[#E60000] text-[#E60000]">
                        {!! $card[2] !!}
                    </div>
                    <p class="text-sm text-[#f59e0b]">{{ $card[0] }}</p>
                </div>
                <p class="text-3xl font-bold ">{{ $card[1] }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Servicios de hoy --}}
        <div class="p-5 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold">Servicios de hoy</h3>
                <a href="{{ route('comunigest.admin.work-orders') }}" class="text-xs text-[#E60000] hover:text-white transition">Ver todos</a>
            </div>
            <div class="space-y-3">
                @forelse($todayServices as $order)
                    <div class="flex items-center justify-between p-2 rounded-xl bg-[#111111]/50">
                        <div>
                            <p class="text-sm font-semibold">{{ $order->community?->name ?? '—' }}</p>
                            <p class="text-[10px] text-[#666666]">{{ $order->starter?->name ?? 'Sin asignar' }} · {{ $order->work_date?->format('d/m') ?? '—' }}</p>
                        </div>
                        @php
                            $serviceBadge = match ($order->status) {
                                'finished' => ['bg-emerald-500', 'Finalizado'],
                                'in_progress' => ['bg-[#E60000]', 'En curso'],
                                'cancelled' => ['bg-[#666666]', 'Cancelado'],
                                default => ['bg-[#f59e0b]', 'Pendiente'],
                            };
                        @endphp
                        <span class="px-2 py-0.5 text-[10px] font-medium text-white rounded {{ $serviceBadge[0] }}">{{ $serviceBadge[1] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[#666666]">No hay servicios hoy</p>
                @endforelse
            </div>
        </div>

        {{-- Misiones pendientes --}}
        <div class="p-5 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A]">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold">Misiones pendientes</h3>
                <a href="{{ route('comunigest.admin.work-orders') }}" class="text-xs text-[#E60000] hover:text-white transition">Ver todas</a>
            </div>
            <div class="space-y-3">
                @forelse($pendingMissions as $task)
                    @php
                        $priorityBadge = match ($task->priority) {
                            'urgent' => ['bg-[#E60000]', 'Alta'],
                            'high' => ['bg-[#f59e0b]', 'Media'],
                            'normal' => ['bg-[#666666]', 'Baja'],
                            default => ['bg-[#666666]', 'Baja'],
                        };
                    @endphp
                    <div class="flex items-center justify-between p-2 rounded-xl bg-[#111111]/50">
                        <div>
                            <p class="text-xs font-semibold leading-tight">{{ $task->title }} · {{ $task->workOrder?->community?->name ?? '' }}</p>
                            <p class="text-[10px] text-[#666666]">{{ $task->workOrder?->work_date?->format('d/m') ?? '—' }}</p>
                        </div>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium text-white rounded {{ $priorityBadge[0] }}">{{ $priorityBadge[1] }}</span>
                    </div>
                @empty
                    <p class="text-sm text-[#666666]">No hay misiones pendientes</p>
                @endforelse
            </div>
        </div>

        {{-- Estado de comunidades --}}
        <div class="p-5 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A]">
            <h3 class="mb-4 font-bold">Estado de comunidades</h3>
            <div class="flex items-center justify-center">
                <div class="relative w-40 h-40 rounded-full" style="background: conic-gradient(
                    #22c55e 0% {{ $communityStatus['stops']['todo'] }}%,
                    #f59e0b {{ $communityStatus['stops']['todo'] }}% {{ $communityStatus['stops']['progreso'] }}%,
                    #E60000 {{ $communityStatus['stops']['progreso'] }}% {{ $communityStatus['stops']['sinIncidir'] }}%,
                    #666666 {{ $communityStatus['stops']['sinIncidir'] }}% 100%
                );">
                    <div class="absolute inset-0 flex items-center justify-center">
                        <div class="w-24 h-24 rounded-full bg-[#2A2A2A]"></div>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-2 mt-6">
                <div class="flex items-center gap-2 text-xs"><span class="w-2 h-2 rounded-full bg-[#22c55e]"></span>Todo el día {{ $communityStatus['todo'] }}%</div>
                <div class="flex items-center gap-2 text-xs"><span class="w-2 h-2 rounded-full bg-[#f59e0b]"></span>En curso {{ $communityStatus['progreso'] }}%</div>
                <div class="flex items-center gap-2 text-xs"><span class="w-2 h-2 rounded-full bg-[#E60000]"></span>Sin incidir {{ $communityStatus['sinIncidir'] }}%</div>
                <div class="flex items-center gap-2 text-xs"><span class="w-2 h-2 rounded-full bg-[#666666]"></span>Resto {{ $communityStatus['resto'] }}%</div>
            </div>
        </div>

    </div>

</div>
