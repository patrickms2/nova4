@php
use Carbon\Carbon;

@endphp


<div class="min-h-screen bg-[#111111]">


    <header class="w-full border-b border-[#2A2A2A] bg-[#111111]/90 backdrop-blur">
        <div class="flex items-center justify-between h-16 px-4 mx-auto max-w-7xl md:px-8">
            <div class="flex items-center gap-3">
                <div class="flex items-center justify-center w-8 h-8 rounded-lg bg-[#E60000]">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6">
                        <path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/>
                    </svg>
                </div>
                <span class="text-lg font-bold tracking-tight">Nova Community</span>
                <span class="text-xs font-bold tracking-tight">{{ auth()->user()?->role }}</span>
            </div>

              <div class="flex items-center gap-3">
                    <button type="button" class="relative p-2 rounded-full bg-[#2A2A2A] text-[#666666]">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M13.73 21a1.999 1.999 0 0 1-3.46 0"/></svg>
                        @if($urgentIncidents->count() > 0)
                            <span class="absolute top-1 right-1 w-2 h-2 rounded-full bg-[#E60000]"></span>
                        @endif
                    </button>

                    <div class="flex items-center justify-center w-8 h-8 rounded-full bg-[#2A2A2A] text-xs font-bold text-[#FFFFFF]">
                        {{ substr(auth()->user()?->firstname ?? auth()->user()?->name ?? 'U', 0, 1) }}
                    </div>
                </div>
        </div>
    </header>
    {{-- Title + filters --}}
    <div class="px-4 pb-4 mt-6">
        <div class="flex items-center justify-between mb-2">
            <h1 class="text-2xl font-bold">Tareas de Mantenimiento</h1>
            @if($selectedDate)
                <button type="button" wire:click="selectDate(null)" class="text-[10px] text-[#E60000]">Ver próximas</button>
            @else
                <button type="button" wire:click="selectDate(null)" class="text-[10px] text-[#E60000]">Ver hoy</button>
            @endif
        </div>
        <p class="text-xs text-[#666666] mb-4">
            @if($selectedDate)
                {{ \Carbon\Carbon::parse($selectedDate)->format('d \d\e F Y') }} · {{ $workOrders->count() }} {{ $workOrders->count() === 1 ? 'gestión' : 'gestiones' }}
            @else
                Próximas gestiones · {{ $workOrders->count() }}
            @endif
        </p>
<div class="flex flex-col gap-3">
            <select wire:model.live="selectedCommunityId" class="w-full bg-[#2A2A2A] border border-[#333] rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#E60000]">
                <option value="">Todas las comunidades</option>
                @foreach($communities as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>

            <input type="date" wire:model.live="selectedDate" class="w-full bg-[#2A2A2A] border border-[#333] rounded-xl px-3 py-2.5 text-sm text-white focus:outline-none focus:ring-2 focus:ring-[#E60000] [color-scheme:dark]" />

            <div class="flex items-center gap-2 text-sm text-[#666666]">
                <input type="checkbox" id="showCommunity" wire:model.live="showCommunity" class="accent-[#E60000]" />
                <label for="showCommunity" class="cursor-pointer select-none">Ver comunidad</label>
            </div>
        </div>

        <div class="flex items-center gap-2 mt-4">
            <button type="button" wire:click="setViewMode('list')" class="flex-1 py-2 text-xs font-semibold rounded-xl {{ $viewMode === 'list' ? 'bg-[#E60000] text-white' : 'bg-[#2A2A2A] text-[#666666]' }}">Lista</button>
            <button type="button" wire:click="setViewMode('calendar')" class="flex-1 py-2 text-xs font-semibold rounded-xl {{ $viewMode === 'calendar' ? 'bg-[#E60000] text-white' : 'bg-[#2A2A2A] text-[#666666]' }}">Calendario</button>
        </div>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-5 gap-2 px-4 mb-6">
        @php
            $pending = $workOrders->sum(fn ($o) => $o->tasks->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->count());
            $done = $workOrders->sum(fn ($o) => $o->tasks->where('status', 'completed')->count());
        @endphp
        <div class="p-2 rounded-2xl bg-[#2A2A2A] text-center">
            <p class="text-base font-bold text-rose-400">{{ $pending }}</p>
            <p class="text-[9px] text-[#666666]">Pendientes</p>
        </div>
        <div class="p-2 rounded-2xl bg-[#2A2A2A] text-center">
            <p class="text-base font-bold text-emerald-400">{{ $done }}</p>
            <p class="text-[9px] text-[#666666]">Realizadas</p>
        </div>
        <div class="p-2 rounded-2xl bg-[#2A2A2A] text-center">
            <p class="text-base font-bold text-amber-400">{{ $urgentIncidents->count() }}</p>
            <p class="text-[9px] text-[#666666]">Incidencias</p>
        </div>
        <div class="p-2 rounded-2xl bg-[#2A2A2A] text-center">
            <p class="text-base font-bold text-blue-400">{{ $communitiesCount }}</p>
            <p class="text-[9px] text-[#666666]">Comunidades</p>
        </div>
        <div class="p-2 rounded-2xl bg-[#2A2A2A] text-center">
            <p class="text-base font-bold text-purple-400">{{ $plansCount }}</p>
            <p class="text-[9px] text-[#666666]">Planes</p>
        </div>
    </div>

    {{-- Urgent notices --}}
    @if($urgentIncidents->count() > 0)
        <div class="px-4 mb-4">
            <div class="p-3 rounded-xl bg-rose-500/15 border border-rose-500/40">
                <p class="text-xs font-semibold text-white">{{ $urgentIncidents->count() }} incidencia(s) urgente(s)</p>
                <p class="text-[10px] text-rose-200">Requiere atención inmediata</p>
            </div>
        </div>
    @endif

    @if($viewMode === 'list')
        @if(!$selectedDate && $upcomingPlans->isNotEmpty())
            <div class="px-4 mb-6">
                <h2 class="text-sm font-bold text-white mb-3">Próximos planes</h2>
                <div class="space-y-3">
                    @foreach($upcomingPlans as $plan)
@if( $plan->workOrders->count())
                    <div class="p-3 rounded-2xl ">
                            <p class="text-sm font-semibold truncate">{{ $plan->community->name ?? '—' }} - {{ $plan->name }}</p>
                            <p class="text-[10px] text-[#666666]">
                                {{ $plan->valid_from?->format('d/m/Y') }} - {{ $plan->valid_until?->format('d/m/Y') }}
                            </p>
                            <p class="text-[10px] text-[#666666] mt-1">{{ $plan->items->count() }} elementos</p>

@forelse($plan->workOrders as $order)
                @php
                    $pendingInOrder = $order->tasks->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->count();
                    $statusClass = match ($order->status) {
                        'in_progress' => 'bg-rose-400 text-rose-950',
                        'finished' => 'bg-emerald-400 text-emerald-950',
                        'cancelled' => 'bg-neutral-300 text-neutral-950',
                        default => 'bg-amber-300 text-amber-950',
                    };
                    $statusLabel = match ($order->status) {
                        'in_progress' => 'En curso', 
                        'finished' => 'Finalizado',
                        'cancelled' => 'Cancelado',
                        default => 'Pendiente',
                    };
                @endphp
                <a href="{{ route('comunigest.work-order', $order) }}" wire:navigate class="block p-3 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A] hover:border-rose-400 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#333] to-[#1a1a1a] flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#666666]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">
                                {{ $plan->community->name ?? '—' }} - {{ $order->reference }} 
                            </p>
                                <p class="text-[12px] text-[#666666]">{{ $order->work_date?->format('d/m/Y') }} - {{ $order->code }}</p>
                            <p class="text-[10px] text-[#666666] truncate">{{ $order->community->address }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded {{ $statusClass }}">{{ $statusLabel }}</span>
                                <span class="text-[10px] text-[#666666]">{{ $pendingInOrder }} {{ $pendingInOrder === 1 ? 'tarea' : 'tareas' }}</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-[#666666] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center rounded-2xl bg-[#2A2A2A]">
                    <p class="text-sm text-[#666666]">
                        @if($selectedDate)
                            No hay gestiones para este día
                        @else
                            No hay próximas gestiones
                        @endif
                    </p>
                    <p class="text-[10px] text-[#666666] mt-1">La oficina añadirá las tareas en breve</p>
                </div>
            @endforelse


                        </div>
                                   @endif
                    @endforeach

                </div>
            </div>
 
        @endif

        {{-- Orders list --}}
        <div class="px-4 space-y-3 pb-6">
            @forelse($workOrders as $order)
                @php
                    $pendingInOrder = $order->tasks->where('status', '!=', 'completed')->where('status', '!=', 'cancelled')->count();
                    $statusClass = match ($order->status) {
                        'in_progress' => 'bg-rose-400 text-rose-950',
                        'finished' => 'bg-emerald-400 text-emerald-950',
                        'cancelled' => 'bg-neutral-300 text-neutral-950',
                        default => 'bg-amber-300 text-amber-950',
                    };
                    $statusLabel = match ($order->status) {
                        'in_progress' => 'En curso',
                        'finished' => 'Finalizado',
                        'cancelled' => 'Cancelado',
                        default => 'Pendiente',
                    };
                @endphp
                <a href="{{ route('comunigest.work-order', $order) }}" wire:navigate class="block p-3 rounded-2xl bg-[#2A2A2A] border border-[#2A2A2A] hover:border-rose-400 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-[#333] to-[#1a1a1a] flex items-center justify-center">
                            <svg class="w-6 h-6 text-[#666666]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M6 10H4a2 2 0 0 0-2 2v7a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-2M10 8h4M10 12h4M14 21v-3a2 2 0 0 0-4 0v3"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold truncate">
                                    {{ $order->community->name }} {{ $order->reference}} 
                            </p>
                                <p class="text-[10px] text-[#666666]">{{ $order->code }} {{ $order->work_date?->format('d/m/Y') }}</p>
                                <p class="text-[10px] text-[#666666] truncate">{{ $order->community->address }}</p>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2 py-0.5 text-[10px] font-medium rounded {{ $statusClass }}">{{ $statusLabel }}</span>
                                <span class="text-[10px] text-[#666666]">{{ $pendingInOrder }} {{ $pendingInOrder === 1 ? 'tarea' : 'tareas' }}</span>
                            </div>
                        </div>
                        <svg class="w-5 h-5 text-[#666666] shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                    </div>
                </a>
            @empty
                <div class="p-8 text-center rounded-2xl bg-[#2A2A2A]">
                    <p class="text-sm text-[#666666]">
                        @if($selectedDate)
                            No hay gestiones para este día
                        @else
                            No hay próximas gestiones
                        @endif
                    </p>
                    <p class="text-[10px] text-[#666666] mt-1">La oficina añadirá las tareas en breve</p>
                </div>
            @endforelse
        </div>
    @endif

    @if($viewMode === 'calendar')
        <div class="px-4 pb-6">
            <div class="w-full flex items-center justify-around  mb-4">
                <button type="button" wire:click="previousMonth" class="p-2 rounded-full bg-[#2A2A2A] text-[#666666]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                </button>
                <p class="text-sm font-semibold text-white">{{ Carbon::parse($calendarDate)->translatedFormat('F Y') }}</p>
                <button type="button" wire:click="nextMonth" class="p-2 rounded-full bg-[#2A2A2A] text-[#666666]">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                </button>
            </div>

            <div class="grid grid-cols-7 gap-1 mb-2 text-center">
                @foreach(['L','M','X','J','V','S','D'] as $day)
                    <span class="text-[10px] font-medium text-[#666666]">{{ $day }}</span>
                @endforeach
            </div>

                @foreach($calendar as $week)

            <div class="w-full grid grid-cols-7 gap-1">
                    @foreach($week as $day)
                        <button type="button" wire:click="selectDay({{ $day['day'] }})" class="aspect-square p-3 rounded-lg text-[10px] relative {{ $day['current'] ? 'bg-[#2A2A2A] text-white' : 'text-[#666666]' }} {{ $day['key'] === $selectedDate ? 'ring-2 ring-[#E60000]' : '' }}"
                        style="    max-width: min-content;
    max-height: min-content;
    margin: auto;">
                            <span class="block text-right">{{ $day['day'] }}</span>
                            @if($day['orders']->isNotEmpty())
                                <span class="absolute bottom-1 left-1/2 -translate-x-1/2 w-1.5 h-1.5 rounded-full bg-[#E60000]"></span>
                                    <span class="sm:block absolute bottom-3 left-1/2 -translate-x-1/2 text-[8px] truncate w-full px-0.5 text-[#E60000]">
                                        {{ $day['orders']->first()->community->name }}
                                    </span>
                            @endif
                        </button>
                    @endforeach
                                </div>

                @endforeach
        </div>
    @endif

</div>
