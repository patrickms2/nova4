<x-filament-panels::page class="nova-community-dashboard">

    {{-- ═══ KPI ribbon ═══ --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Órdenes hoy',     'value' => $kpis['todayOrders'],       'color' => 'text-primary-600 dark:text-primary-400'],
            ['label' => 'Pendientes',       'value' => $kpis['pendingOrders'],     'color' => 'text-amber-600 dark:text-amber-400'],
            ['label' => 'Incidencias',      'value' => $kpis['openIncidents'],     'color' => 'text-rose-600 dark:text-rose-400'],
            ['label' => 'Tickets abiertos', 'value' => $kpis['openTickets'],       'color' => 'text-orange-600 dark:text-orange-400'],
            ['label' => 'Empleados activos','value' => $kpis['activeEmployees'],   'color' => 'text-emerald-600 dark:text-emerald-400'],
            ['label' => 'Planes activos',   'value' => $kpis['activePlans'],       'color' => 'text-blue-600 dark:text-blue-400'],
        ] as $kpi)
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $kpi['label'] }}</div>
                <div class="mt-1 text-2xl font-bold {{ $kpi['color'] }}">{{ $kpi['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- ═══ Main card grid ═══ --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">

        {{-- ─── Órdenes de hoy ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Órdenes de hoy</h3>
                <span class="inline-flex items-center rounded-full bg-primary-50 px-2.5 py-0.5 text-xs font-medium text-primary-600 dark:bg-primary-900 dark:text-primary-300">
                    {{ $kpis['todayOrders'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Pendientes / En curso</div>
                    <div class="text-xl font-semibold tracking-tight text-amber-600 dark:text-amber-400">{{ $kpis['pendingOrders'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Próximas citas</div>
                    <div class="text-xl font-semibold tracking-tight">{{ $kpis['upcomingCitas'] }}</div>
                </div>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($latestOrders as $order)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <div class="min-w-0 text-sm">
                            <div class="truncate font-medium">{{ $order->code }}</div>
                            <div class="text-xs text-gray-500">{{ $order->community?->name }}</div>
                        </div>
                        <div class="ml-2 shrink-0 text-right text-xs">
                            <span @class([
                                'inline-block rounded px-1.5 py-0.5 font-semibold uppercase',
                                'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' => $order->status === 'pending',
                                'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'   => $order->status === 'in_progress',
                                'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' => $order->status === 'completed',
                            ])>{{ $order->status }}</span>
                            <div class="mt-0.5 text-gray-400">{{ $order->completed_tasks_count }}/{{ $order->tasks_count }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin órdenes para hoy.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['workOrders'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver órdenes
                </a>
            </div>
        </x-filament::card>

        {{-- ─── Incidencias ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Incidencias</h3>
                <span class="inline-flex items-center rounded-full bg-rose-50 px-2.5 py-0.5 text-xs font-medium text-rose-600 dark:bg-rose-900 dark:text-rose-300">
                    {{ $kpis['openIncidents'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Abiertas</div>
                    <div class="text-xl font-semibold tracking-tight text-rose-600 dark:text-rose-400">{{ $kpis['openIncidents'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Urgentes</div>
                    <div class="text-xl font-semibold tracking-tight text-red-700 dark:text-red-400">{{ $kpis['urgentIncidents'] }}</div>
                </div>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($latestIncidents as $incident)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <div class="min-w-0 text-sm">
                            <div class="truncate font-medium">{{ $incident->title }}</div>
                            <div class="text-xs text-gray-500">{{ $incident->community?->name }} · {{ $incident->created_at->diffForHumans() }}</div>
                        </div>
                        <span @class([
                            'ml-2 shrink-0 inline-block rounded px-1.5 py-0.5 text-xs font-semibold uppercase',
                            'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300'       => $incident->priority === 'urgent',
                            'bg-orange-100 text-orange-700 dark:bg-orange-900 dark:text-orange-300' => $incident->priority === 'high',
                            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'   => ! in_array($incident->priority, ['urgent', 'high']),
                        ])>{{ $incident->priority }}</span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin incidencias abiertas.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['incidents'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver incidencias
                </a>
            </div>
        </x-filament::card>

        {{-- ─── Tickets propietarios ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Tickets propietarios</h3>
                <span class="inline-flex items-center rounded-full bg-orange-50 px-2.5 py-0.5 text-xs font-medium text-orange-600 dark:bg-orange-900 dark:text-orange-300">
                    {{ $kpis['openTickets'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Abiertos</div>
                    <div class="text-xl font-semibold tracking-tight text-orange-600 dark:text-orange-400">{{ $kpis['openTickets'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">En gestión</div>
                    <div class="text-xl font-semibold tracking-tight">{{ $kpis['inProgressTickets'] }}</div>
                </div>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($latestTickets as $ticket)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <div class="min-w-0 text-sm">
                            <div class="truncate font-medium">{{ $ticket->title }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $ticket->person?->display_name ?? $ticket->person?->name }}
                                @if ($ticket->community) · {{ $ticket->community->name }} @endif
                            </div>
                        </div>
                        <span @class([
                            'ml-2 shrink-0 inline-block rounded px-1.5 py-0.5 text-xs font-semibold uppercase',
                            'bg-amber-100 text-amber-700 dark:bg-amber-900 dark:text-amber-300' => $ticket->status === 'open',
                            'bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300'    => $ticket->status === 'in_progress',
                            'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'   => ! in_array($ticket->status, ['open', 'in_progress']),
                        ])>{{ $ticket->status }}</span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin tickets pendientes.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['tickets'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver tickets
                </a>
            </div>
        </x-filament::card>

        {{-- ─── Estado comunidades ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Comunidades</h3>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300">
                    {{ $kpis['activeCommunities'] }}
                </span>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($communities as $community)
                    <div class="rounded-lg bg-gray-50 p-3 dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <div class="text-sm font-medium">{{ $community->name }}</div>
                            @if ($community->open_incidents_count > 0)
                                <span class="inline-block rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700 dark:bg-rose-900 dark:text-rose-300">
                                    {{ $community->open_incidents_count }} incid.
                                </span>
                            @endif
                        </div>
                        <div class="mt-1 flex gap-4 text-xs text-gray-500">
                            <span>{{ $community->pending_orders_count }} órdenes</span>
                            <span>{{ $community->active_plans_count }} planes</span>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin comunidades activas.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['communities'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver comunidades
                </a>
            </div>
        </x-filament::card>

        {{-- ─── Planes activos ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Planes activos</h3>
                <span class="inline-flex items-center rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900 dark:text-blue-300">
                    {{ $kpis['activePlans'] }}
                </span>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($latestPlans as $plan)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <div class="min-w-0 text-sm">
                            <div class="truncate font-medium">{{ $plan->name }}</div>
                            <div class="text-xs text-gray-500">
                                {{ $plan->community?->name }}
                                @if ($plan->valid_until)
                                    · hasta {{ $plan->valid_until->format('d/m/Y') }}
                                @endif
                            </div>
                        </div>
                        <span class="ml-2 shrink-0 inline-block rounded bg-blue-100 px-1.5 py-0.5 text-xs font-semibold text-blue-700 dark:bg-blue-900 dark:text-blue-300 uppercase">
                            {{ $plan->status }}
                        </span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin planes activos.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['plans'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver planes
                </a>
            </div>
        </x-filament::card>

        {{-- ─── Empleados ─── --}}
        <x-filament::card class="flex h-full flex-col">
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-semibold">Empleados</h3>
                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-600 dark:bg-emerald-900 dark:text-emerald-300">
                    {{ $kpis['activeEmployees'] }}
                </span>
            </div>

            <div class="mt-4 grid grid-cols-2 gap-4">
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Activos</div>
                    <div class="text-xl font-semibold tracking-tight text-emerald-600 dark:text-emerald-400">{{ $kpis['activeEmployees'] }}</div>
                </div>
                <div>
                    <div class="text-sm text-gray-500 dark:text-gray-400">Comunidades</div>
                    <div class="text-xl font-semibold tracking-tight">{{ $kpis['activeCommunities'] }}</div>
                </div>
            </div>

            <div class="mt-4 flex-1 space-y-2">
                @forelse ($communities->take(5) as $community)
                    <div class="flex items-center justify-between rounded-lg bg-gray-50 p-2 dark:bg-gray-900">
                        <div class="text-sm font-medium">{{ $community->name }}</div>
                        <span class="text-xs text-gray-500">{{ $community->pending_orders_count }} pendientes</span>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">Sin comunidades asignadas.</div>
                @endforelse
            </div>

            <div class="mt-4 flex flex-wrap gap-2">
                <a href="{{ $urls['employees'] }}" class="inline-flex items-center justify-center rounded-lg bg-primary-600 px-3 py-2 text-sm font-medium text-white transition hover:bg-primary-500">
                    Ver empleados
                </a>
            </div>
        </x-filament::card>

    </div>

</x-filament-panels::page>
