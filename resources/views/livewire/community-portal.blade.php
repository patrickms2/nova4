@php
    $communityPortalUser = auth()->user();
    $communityUnreadNotifications = $communityPortalUser
        && \Illuminate\Support\Facades\Schema::hasTable('notifications')
        && method_exists($communityPortalUser, 'unreadNotifications')
            ? $communityPortalUser->unreadNotifications()->count()
            : 0;
    $communityPortalName = $portalType === 'owner' ? $person->display_name : $employee->name;
    $communityPortalInitials = str($communityPortalName)->explode(' ')->filter()->take(2)->map(fn ($part) => str($part)->substr(0, 1))->join('');
@endphp

<div
    data-community-portal
    x-data="{
        showFilters: false,
        showSpotlight: false,
        showTicketModal: false,
        employeeEntryModal: null,
        ownerEntryModal: null,
        attendanceModal: false,
        locating: false,
        locationError: '',
        openAttendance() {
            this.attendanceModal = true;
            this.locationError = '';
            this.locating = true;

            if (! navigator.geolocation) {
                this.locating = false;
                this.locationError = 'Este dispositivo no permite obtener la ubicación.';
                return;
            }

            navigator.geolocation.getCurrentPosition(
                position => {
                    this.$wire.set('attendanceLatitude', position.coords.latitude);
                    this.$wire.set('attendanceLongitude', position.coords.longitude);
                    this.$wire.set('attendanceAccuracy', Math.round(position.coords.accuracy));
                    this.locating = false;
                },
                () => {
                    this.locating = false;
                    this.locationError = 'Activa el permiso de ubicación para registrar la sesión.';
                },
                { enableHighAccuracy: true, timeout: 15000, maximumAge: 0 },
            );
        },
    }"
    x-effect="$wire.set('employeeEntryType', employeeEntryModal ?? '')"
    x-on:community-section-changed.window="showFilters = false"
    x-on:community-ticket-created.window="showTicketModal = false"
    x-on:community-employee-entry-created.window="employeeEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))"
    x-on:community-owner-entry-created.window="ownerEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))"
    x-on:community-attendance-recorded.window="attendanceModal = false"
    x-on:keydown.meta.k.window.prevent="showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus())"
    x-on:keydown.ctrl.k.window.prevent="showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus())"
    x-on:keydown.slash.window.prevent="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement?.tagName)) { showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus()) }"
    x-on:keydown.escape.window="showSpotlight = false; showTicketModal = false; employeeEntryModal = null; ownerEntryModal = null; attendanceModal = false; window.dispatchEvent(new CustomEvent('community-camera-reset'))"
    class="min-h-screen bg-[radial-gradient(circle_at_top,rgba(185,28,28,0.10),transparent_34%),#05080c] px-3 pb-24 pt-4 text-white sm:px-5"
>
    <div class="mx-auto flex max-w-5xl flex-col gap-5">
        <header class="community-topbar" aria-label="Cabecera NOVA Community">
            <button type="button" class="community-topbar__edge" wire:click="show('home')" aria-label="Ir al inicio" title="Inicio">
                <x-heroicon-o-chevron-right class="h-6 w-6" />
            </button>

            <button type="button" wire:click="show('home')" class="community-topbar__brand" aria-label="NOVA Community · Inicio">
                <img src="{{ asset('logos/logo_nova4.png') }}" alt="NOVA" class="h-7 w-auto brightness-0 invert sm:h-8">
                <span>COMMUNITY</span>
            </button>

            <div class="community-topbar__actions">
                <button type="button" x-on:click="showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus())" class="community-topbar__icon" aria-label="Abrir Spotlight" title="Spotlight (⌘K)">
                    <x-heroicon-o-magnifying-glass class="h-5 w-5" />
                </button>
                <button type="button" x-on:click="showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus())" class="community-topbar__icon relative" aria-label="Notificaciones" title="Notificaciones">
                    <x-heroicon-o-bell class="h-5 w-5" />
                    @if ($communityUnreadNotifications > 0)
                        <span class="community-topbar__badge">{{ min($communityUnreadNotifications, 99) }}</span>
                    @endif
                </button>
                <button type="button" x-on:click="showSpotlight = true; $nextTick(() => $refs.spotlightInput?.focus())" class="community-topbar__avatar" aria-label="Menú de {{ $communityPortalName }}" title="{{ $communityPortalName }}">
                    {{ $communityPortalInitials }}
                </button>
            </div>
        </header>

        @if ($message)
            <div class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-300">{{ $message }}</div>
        @endif

        @if ($section !== 'home')
            @php
                $sectionLabels = [
                    'properties' => 'Propiedades', 'documents' => 'Documentos', 'fees' => 'Cuotas',
                    'appointments' => 'Citas', 'tickets' => 'Tickets', 'plans' => 'Planes',
                    'work' => 'Ordenes', 'incidents' => 'Incidencias', 'shifts' => 'Turnos',
                    'attendance' => 'Asistencia', 'expenses' => 'Gastos','communities' => 'Comunidades'
                ];
            @endphp
            <div class="community-glass flex items-center justify-between gap-3 rounded-2xl p-3">
                <div class="flex min-w-0 items-center gap-3">
                    <button type="button" wire:click="show('home')" class="community-icon-button shrink-0" aria-label="Volver al inicio" title="Volver">
                        <x-heroicon-o-chevron-left class="h-5 w-5" />
                    </button>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/35">Listado</p>
                        <h2 class="truncate text-lg font-bold">{{ $sectionLabels[$section] ?? ucfirst($section) }}</h2>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if ($portalType === 'owner' && $section === 'tickets')
                        <button type="button" x-on:click="showTicketModal = true" class="community-button community-button-primary" aria-label="Añadir ticket">
                            <x-heroicon-o-plus class="h-4 w-4" /> <span class="hidden sm:inline">Añadir</span>
                        </button>
                    @endif
                    @if ($portalType === 'owner' && in_array($section, ['appointments', 'documents', 'incidents'], true))
                        <button type="button" x-on:click="ownerEntryModal = '{{ ['appointments' => 'appointment', 'documents' => 'document', 'incidents' => 'incident'][$section] }}'" class="community-button community-button-primary" aria-label="Añadir registro">
                            <x-heroicon-o-plus class="h-4 w-4" /> <span class="hidden sm:inline">Añadir</span>
                        </button>
                    @endif
                    @if ($portalType === 'employee' && in_array($section, ['appointments', 'documents', 'tickets', 'incidents', 'expenses'], true))
                        <button type="button" x-on:click="employeeEntryModal = '{{ ['appointments' => 'appointment', 'documents' => 'document', 'tickets' => 'ticket', 'incidents' => 'incident', 'expenses' => 'expense'][$section] }}'" class="community-button community-button-primary" aria-label="Añadir registro">
                            <x-heroicon-o-plus class="h-4 w-4" /> <span class="hidden sm:inline">Añadir</span>
                        </button>
                    @endif
                    <button type="button" x-on:click="showFilters = ! showFilters" x-bind:aria-expanded="showFilters" class="community-icon-button" aria-label="Mostrar filtros">
                        <x-heroicon-o-adjustments-horizontal class="h-5 w-5" />
                    </button>
                </div>
            </div>

            <div x-cloak x-show="showFilters" x-collapse class="community-glass rounded-2xl p-4">
                <div class="grid gap-3 sm:grid-cols-[1fr_auto_auto]">
                    <label class="relative block">
                        <span class="sr-only">Buscar</span>
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-3 h-4 w-4 text-white/35" />
                        <input wire:model.live.debounce.300ms="search" type="search" class="community-input pl-10" placeholder="Buscar en esta sección…">
                    </label>
                    <select wire:model.live="statusFilter" class="community-input min-w-44">
                        <option value="">Todos los estados</option>
                        <option value="open">Abierto</option>
                        <option value="pending">Pendiente</option>
                        <option value="in_progress">En curso</option>
                        <option value="active">Activo</option>
                        <option value="resolved">Resuelto</option>
                    </select>
                    @if ($portalType === 'employee' && $section === 'work')
                        <select wire:model.live="plansFilter" class="community-input min-w-44">
                            <option value="">Todos los planes</option>
                            @foreach ($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }}</option>@endforeach
                        </select>
                        <select wire:model.live="sourceFilter" class="community-input min-w-44">
                            <option value="">Todos los orígenes</option>
                            <option value="community_ticket">Desde incidencia</option>
                        </select>
                    @endif

                    <button type="button" wire:click="clearFilters" class="community-button community-button-muted">Limpiar</button>
                </div>
                <div wire:loading.flex wire:target="search,statusFilter,plansFilter,sourceFilter,clearFilters" class="mt-3 items-center gap-2 text-xs text-white/45">
                    <span class="h-3 w-3 animate-spin rounded-full border-2 border-white/20 border-t-red-500"></span> Actualizando resultados
                </div>
            </div>
        @endif

        @if ($portalType === 'owner')
            @php
                $ownerCards = [
                    ['properties', 'PROPIEDADES', $person->properties->count(), 'Unidades', '⌂', 'cyan'],
                    ['documents', 'DOCUMENTOS', $documents->count(), 'PDFs y archivos', '▤', 'amber'],
                    ['fees', 'CUOTAS', $fees->where('status', '!=', 'paid')->count(), 'Pendientes', '€', 'emerald'],
                    ['appointments', 'CITAS', $appointments->count(), 'Próximas', '□', 'blue'],
                    ['tickets', 'TICKETS', $tickets->whereNotIn('status', ['resolved', 'closed'])->count(), 'Abiertos', '!', 'red'],
                    ['incidents', 'INCIDENCIAS', $ownerIncidents->whereNotIn('status', ['resolved', 'closed'])->count(), 'Abiertas', '!', 'red'],
                ];
            @endphp

            @if ($section === 'home')
                <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
                    @foreach ($ownerCards as [$key, $label, $count, $description, $icon, $tone])
                        @php $toneClass = match ($tone) { 'cyan' => 'border-cyan-500/25 bg-cyan-500/10 text-cyan-400', 'amber' => 'border-amber-500/25 bg-amber-500/10 text-amber-400', 'emerald' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', 'blue' => 'border-blue-500/25 bg-blue-500/10 text-blue-400', default => 'border-red-500/25 bg-red-500/10 text-red-400' }; @endphp
                        <button wire:key="owner-card-{{ $key }}" wire:click="show('{{ $key }}')" class="group min-h-32 rounded-2xl border border-white/10 bg-[#161b21] p-4 text-left shadow-xl transition hover:border-white/25 hover:bg-[#1b2128]">
                            <div class="flex items-start justify-between gap-3"><div><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">{{ $label }}</p><p class="mt-3 text-3xl font-light">{{ $count }}</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl border text-xl font-bold {{ $toneClass }}">{{ $icon }}</span></div>
                            <p class="mt-2 text-xs text-gray-500">{{ $description }}</p>
                        </button>
                    @endforeach
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <article class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">PRÓXIMA CITA</p><p class="mt-2 font-semibold">{{ $appointments->first()?->starts_at?->format('d/m/Y H:i') ?? 'Sin citas próximas' }}</p></article>
                    <article class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">CUOTAS PENDIENTES</p><p class="mt-2 text-xl font-semibold text-amber-300">{{ number_format((float) $fees->where('status', '!=', 'paid')->sum('amount'), 2, ',', '.') }} €</p></article>
                    <article class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">ESTADO</p><p class="mt-2 font-semibold text-emerald-400">● Operativo</p></article>
                </div>
                <section class="rounded-3xl border border-white/15 bg-[#14191f] p-5"><p class="text-center text-lg font-bold">¡Hola, {{ $person->first_name ?: $person->display_name }}!</p><p class="mt-1 text-center text-xs text-gray-500">{{ $person->communities->pluck('name')->join(' · ') }}</p></section>
            @elseif ($section === 'properties')
                <div class="grid gap-3 md:grid-cols-2">@forelse ($person->properties as $property)<article wire:key="property-{{ $property->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><div class="flex items-center justify-between"><h2 class="font-bold">{{ $property->name }}</h2><span class="rounded-full bg-emerald-500/10 px-2 py-1 text-[10px] text-emerald-400">ACTIVA</span></div><p class="mt-2 text-sm text-gray-400">{{ $property->unit_reference }} · {{ $property->community?->name }}</p><p class="mt-1 text-xs text-gray-500">{{ $property->address }}</p></article>@empty<x-community-portal-empty text="Sin propiedades relacionadas" />@endforelse</div>
            @elseif ($section === 'documents')
                <div class="grid gap-3">@forelse ($documents as $document)<x-community-record-row wire:key="document-{{ $document->id }}" :record="$document" type="document" tone="amber" :subtitle="($document->documentType?->name ?? $document->type).' · '.($document->property?->name ?? 'Comunidad')" />@empty<x-community-portal-empty text="Sin documentos" />@endforelse</div>
            @elseif ($section === 'fees')
                <div class="grid gap-3">@forelse ($fees as $fee)<article wire:key="fee-{{ $fee->id }}" wire:click="openDetail('fee', {{ $fee->id }})" class="cursor-pointer rounded-2xl border border-white/10 bg-[#161b21] p-4"><div class="flex items-center justify-between"><div><h2 class="font-semibold">{{ $fee->concept }}</h2><p class="mt-1 text-xs text-gray-500">{{ $fee->property?->name }} · {{ $fee->period->format('m/Y') }}</p></div><div class="text-right"><p class="text-lg font-bold">{{ number_format((float) $fee->amount, 2, ',', '.') }} €</p><p class="text-[10px] {{ $fee->status === 'paid' ? 'text-emerald-400' : 'text-amber-400' }}">{{ strtoupper($fee->status) }}</p></div></div></article>@empty<x-community-portal-empty text="Sin cuotas" />@endforelse</div>
            @elseif ($section === 'appointments')
                <div class="grid gap-3">@forelse ($appointments as $appointment)<x-community-appointment-row wire:key="appointment-{{ $appointment->id }}" :appointment="$appointment" />@empty<x-community-portal-empty text="Sin citas" />@endforelse</div>
            @elseif ($section === 'tickets')
                <div class="grid gap-3">@forelse ($tickets as $ticket)<x-community-record-row wire:key="ticket-{{ $ticket->id }}" :record="$ticket" type="ticket" :subtitle="($ticket->property?->name ?? 'General').' · '.($ticket->community?->name ?? 'Comunidad')" />@empty<x-community-portal-empty text="Sin tickets" />@endforelse</div>
            @elseif ($section === 'incidents')
                <div class="grid gap-3">@forelse ($ownerIncidents as $incident)<x-community-record-row wire:key="owner-incident-{{ $incident->id }}" :record="$incident" type="incident" :subtitle="($incident->property?->name ?? 'General').' · '.($incident->community?->name ?? 'Comunidad')" />@empty<x-community-portal-empty text="Sin incidencias" />@endforelse</div>
            @endif
        @else
            @php
                $employeeCards = [
                    ['communities', 'COMUNIDADES', $employeeCommunities->count(), 'Comunidades', '≡', 'amber'],
                    ['plans', 'PLANES', $plans->count(), 'Mantenimiento', '≡', 'violet'],
                    ['work', 'ÓRDENES', $workOrders->count(), 'Pendientes', '▤', 'cyan'],
                    ['incidents', 'INCIDENCIAS', $incidents->count() + $tickets->count(), 'Requieren atención', '!', 'red'],
                    ['shifts', 'TURNOS', $shifts->count(), 'Próximos', '□', 'blue'],
                    ['attendance', 'ASISTENCIA', $attendances->count(), 'Registros', '◷', 'emerald'],
                    ['appointments', 'CITAS', $employeeAppointments->count(), 'Solicitudes', '□', 'blue'],
                    ['documents', 'DOCUMENTOS', $employeeDocuments->count(), 'Archivos enviados', '▤', 'violet'],
                    ['tickets', 'TICKETS', $employeeTickets->whereNotIn('status', ['resolved', 'closed'])->count(), 'Solicitudes', '!', 'red'],
                    ['expenses', 'GASTOS', $expenseTickets->count(), 'Justificantes', '€', 'amber'],
                ];
            @endphp


            @if ($section === 'home')
              <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
                @foreach ($employeeCards as [$key, $label, $count, $description, $icon, $tone])
                    @php $toneClass = match ($tone) { 'violet' => 'border-violet-500/25 bg-violet-500/10 text-violet-400', 'cyan' => 'border-cyan-500/25 bg-cyan-500/10 text-cyan-400', 'blue' => 'border-blue-500/25 bg-blue-500/10 text-blue-400', 'emerald' => 'border-emerald-500/25 bg-emerald-500/10 text-emerald-400', default => 'border-red-500/25 bg-red-500/10 text-red-400' }; @endphp
                    <button wire:key="employee-card-{{ $key }}" wire:click="show('{{ $key }}')" class="min-h-32 rounded-2xl border p-4 text-left shadow-xl transition {{ $section === $key ? 'border-red-500/70 bg-[#20252b]' : 'border-white/10 bg-[#161b21] hover:border-white/25' }}"><div class="flex justify-between gap-3"><div><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">{{ $label }}</p><p class="mt-3 text-3xl font-light">{{ $count }}</p></div><span class="flex h-11 w-11 items-center justify-center rounded-xl border text-xl font-bold {{ $toneClass }}">{{ $icon }}</span></div><p class="mt-2 text-xs text-gray-500">{{ $description }}</p></button>
                @endforeach
            </div>
            @php $todayAttendance = $attendances->first(fn ($item) => $item->attendance_date?->isToday()); @endphp
                <div class="grid gap-3 sm:grid-cols-2"><article class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">PRÓXIMO TURNO</p><p class="mt-2 font-semibold">{{ $shifts->first()?->shift_date?->format('d/m/Y') ?? 'Sin turno' }} {{ $shifts->first()?->starts_at }}</p></article><button type="button" x-on:click="openAttendance()" @disabled($todayAttendance?->checked_out_at) class="rounded-2xl border border-red-500/20 bg-[#161b21] p-4 text-left disabled:cursor-not-allowed disabled:opacity-50"><p class="text-[10px] font-bold tracking-[0.18em] text-gray-400">REGISTRO</p><p class="mt-2 font-semibold {{ $todayAttendance?->checked_in_at ? 'text-emerald-400' : 'text-red-300' }}">{{ ! $todayAttendance?->checked_in_at ? 'Registrar entrada' : (! $todayAttendance?->checked_out_at ? 'Registrar salida' : 'Jornada cerrada') }}</p></button></div>
                <section class="rounded-3xl border border-white/15 bg-[#14191f] p-5"><p class="text-center text-lg font-bold">¡Hola, {{ $employee->name }}!</p><p class="mt-1 text-center text-xs text-gray-500">{{ $employee->communityDepartments->pluck('name')->join(' · ') }}</p></section>
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <button type="button" x-on:click="employeeEntryModal = 'appointment'" class="community-quick-action"><span class="bg-blue-500/15 text-blue-300"><x-heroicon-o-calendar-days class="h-5 w-5" /></span><strong>NUEVA CITA</strong><small>Solicitar</small></button>
                    <button type="button" x-on:click="employeeEntryModal = 'document'" class="community-quick-action"><span class="bg-violet-500/15 text-violet-300"><x-heroicon-o-document-arrow-up class="h-5 w-5" /></span><strong>DOCUMENTO</strong><small>Subir archivo</small></button>
                    <button type="button" x-on:click="employeeEntryModal = 'incident'" class="community-quick-action"><span class="bg-red-500/15 text-red-300"><x-heroicon-o-camera class="h-5 w-5" /></span><strong>INCIDENCIA</strong><small>Foto y descripción</small></button>
                    <button type="button" x-on:click="employeeEntryModal = 'expense'" class="community-quick-action"><span class="bg-amber-500/15 text-amber-300"><x-heroicon-o-banknotes class="h-5 w-5" /></span><strong>GASTO</strong><small>Ticket con foto</small></button>
                </div>
            @elseif ($section === 'communities')
                <div class="grid gap-3">
                    @forelse ($employeeCommunities as $community)
                        <article wire:key="community-{{ $community->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <h2 class="font-semibold">{{ $community->name }}</h2>
                                <button type="button" wire:click="showCommunityPlans({{ $community->id }})" class="shrink-0 rounded-xl bg-red-600 px-3 py-2 text-xs font-bold">Ver planes</button>
                            </div>
                        </article>
                    @empty
                        <x-community-portal-empty text="Sin comunidades asignadas" />
                    @endforelse
                </div>

            @elseif ($section === 'plans')
                <div class="grid gap-3">
                    @forelse ($plans as $plan)
                        <article wire:key="plan-{{ $plan->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="text-[10px] font-bold tracking-[0.16em] text-red-400">{{ $plan->community?->name }} · {{ $plan->workOrders->count() }} órdenes</p>
                                    <h2 class="mt-1 font-semibold">{{ $plan->name }}</h2>
                                    <p class="mt-1 text-xs text-gray-500">{{ $plan->valid_from?->format('d/m/Y') }} → {{ $plan->valid_until?->format('d/m/Y') ?? 'Sin fin' }}</p>
                                </div>
                                <button type="button" wire:click="showPlanOrders({{ $plan->id }})" class="shrink-0 rounded-xl bg-red-600 px-3 py-2 text-xs font-bold">Ver órdenes</button>
                            </div>
                        </article>
                    @empty
                        <x-community-portal-empty text="Sin planes activos" />
                    @endforelse
                </div>

            @elseif ($section === 'work')
                <div class="grid gap-3">
                    @forelse ($workOrders as $order)
                        <article wire:key="order-{{ $order->id }}" class="rounded-2xl border border-red-500/20 bg-[#18191e] p-4">
                        <x-community-orders-row wire:key="order-{{ $order->id }}" :order="$order" type="order" :subtitle="($order->community?->name ?? 'Comunidad').' · '.($order?->code ?? 'Sin orden')" />
                        </article>
                    @empty
                        <x-community-portal-empty text="Sin órdenes pendientes" />
                    @endforelse
                </div>
            @elseif ($section === 'incidents')
                <div class="grid gap-3">
   
                    @forelse ($incidents as $incident)
                        <x-community-record-row wire:key="incident-{{ $incident->id }}" :record="$incident" type="incident" :subtitle="($incident->community?->name ?? 'Comunidad').' · '.($incident->workOrder?->code ?? 'Sin orden')" />
                    @empty
                        @if ($tickets->isEmpty())<x-community-portal-empty text="Sin incidencias abiertas" />@endif
                    @endforelse
                </div>
            @elseif ($section === 'shifts')
                <div class="grid gap-3">@forelse ($shifts as $shift)<article wire:key="shift-{{ $shift->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><div class="flex gap-4"><div class="flex h-14 w-14 shrink-0 flex-col items-center justify-center rounded-xl bg-blue-500/10 text-blue-300"><span class="text-[10px]">{{ $shift->shift_date->translatedFormat('M') }}</span><strong class="text-xl">{{ $shift->shift_date->day }}</strong></div><div><h2 class="font-semibold">{{ $shift->starts_at }}–{{ $shift->ends_at }}</h2><p class="mt-1 text-sm text-gray-400">{{ $shift->community?->name }}</p><p class="text-xs text-gray-500">{{ $shift->department?->name }} · {{ $shift->status }}</p></div></div></article>@empty<x-community-portal-empty text="Sin turnos próximos" />@endforelse</div>
            @elseif ($section === 'attendance')
                <button type="button" x-on:click="openAttendance()" class="w-full rounded-2xl bg-red-600 px-5 py-4 text-sm font-bold shadow-lg shadow-red-950/30">Registrar entrada / salida</button><div class="grid gap-3">@forelse ($attendances as $attendance)<article wire:key="attendance-{{ $attendance->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><div class="flex justify-between"><div><h2 class="font-semibold">{{ $attendance->attendance_date->format('d/m/Y') }}</h2><p class="mt-1 text-xs text-gray-500">{{ $attendance->communities->pluck('name')->join(' · ') ?: $attendance->community?->name }} · {{ $attendance->type }} · {{ $attendance->status }}</p></div><p class="text-sm text-emerald-300">{{ $attendance->checked_in_at?->format('H:i') ?? '--:--' }}–{{ $attendance->checked_out_at?->format('H:i') ?? '--:--' }}</p></div>@if ($attendance->notes)<p class="mt-3 border-t border-white/10 pt-3 text-sm text-white/60">{{ $attendance->notes }}</p>@endif</article>@empty<x-community-portal-empty text="Sin registros de asistencia" />@endforelse</div>
            @elseif ($section === 'appointments')
                <div class="grid gap-3">@forelse ($employeeAppointments as $appointment)<x-community-appointment-row wire:key="employee-appointment-{{ $appointment->id }}" :appointment="$appointment" />@empty<x-community-portal-empty text="Sin citas creadas" />@endforelse</div>
            @elseif ($section === 'documents')
                <div class="grid gap-3">@forelse ($employeeDocuments as $document)<x-community-record-row wire:key="employee-document-{{ $document->id }}" :record="$document" type="document" tone="amber" :subtitle="($document->community?->name ?? 'Comunidad').' · '.$document->filename" />@empty<x-community-portal-empty text="Sin documentos enviados" />@endforelse</div>
            @elseif ($section === 'tickets')
                <div class="grid gap-3">@forelse ($employeeTickets as $ticket)<x-community-record-row wire:key="employee-ticket-{{ $ticket->id }}" :record="$ticket" type="ticket" :subtitle="($ticket->community?->name ?? 'Comunidad').' · '.$ticket->created_at->format('d/m/Y H:i')" />@empty<x-community-portal-empty text="Sin tickets enviados" />@endforelse</div>
            @elseif ($section === 'expenses')
                <div class="grid gap-3">@forelse ($expenseTickets as $expense)<article wire:key="employee-expense-{{ $expense->id }}" class="rounded-2xl border border-white/10 bg-[#161b21] p-4"><div class="flex justify-between gap-3"><div><h2 class="font-semibold">{{ $expense->title }}</h2><p class="mt-1 text-xs text-gray-500">{{ $expense->community?->name }} · {{ $expense->created_at->format('d/m/Y') }}</p></div><p class="font-bold text-amber-300">{{ number_format((float) $expense->amount, 2, ',', '.') }} €</p></div></article>@empty<x-community-portal-empty text="Sin gastos enviados" />@endforelse</div>
            @endif
        @endif
    </div>

    <div x-cloak x-show="showSpotlight" x-transition.opacity class="fixed inset-0 z-[100] flex items-center justify-center bg-black/80 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-label="Spotlight">
        <button type="button" class="absolute inset-0" x-on:click="showSpotlight = false" aria-label="Cerrar Spotlight"></button>
        <section x-show="showSpotlight" x-transition class="community-glass relative z-10 w-full max-w-3xl rounded-3xl p-4 sm:p-5">
            <div class="flex items-center gap-3">
                <label class="relative flex-1">
                    <span class="sr-only">Buscar en Community</span>
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-3.5 h-5 w-5 text-white/40" />
                    <input x-ref="spotlightInput" wire:model.live.debounce.300ms="search" type="search" class="community-input h-12 pl-12" placeholder="Buscar propiedades, documentos, órdenes…">
                </label>
                <button type="button" x-on:click="showSpotlight = false" class="community-button community-button-muted">ESC</button>
            </div>
            <p class="mb-3 mt-5 text-[10px] font-bold uppercase tracking-[0.2em] text-white/35">Acciones rápidas</p>
            <div class="grid gap-3 sm:grid-cols-2">
                <button type="button" x-on:click="showSpotlight = false; {{ $portalType === 'owner' ? "ownerEntryModal = 'appointment'" : "employeeEntryModal = 'appointment'" }}" class="community-quick-action"><span class="bg-blue-500/15 text-blue-300"><x-heroicon-o-calendar-days class="h-5 w-5" /></span><strong>CITA</strong><small>Solicitar cita</small></button>
                <button type="button" x-on:click="showSpotlight = false; {{ $portalType === 'owner' ? 'showTicketModal = true' : "employeeEntryModal = 'ticket'" }}" class="community-quick-action"><span class="bg-red-500/15 text-red-300"><x-heroicon-o-ticket class="h-5 w-5" /></span><strong>TICKET</strong><small>Nueva solicitud</small></button>
                <button type="button" x-on:click="showSpotlight = false; {{ $portalType === 'owner' ? "ownerEntryModal = 'document'" : "employeeEntryModal = 'document'" }}" class="community-quick-action"><span class="bg-amber-500/15 text-amber-300"><x-heroicon-o-document-arrow-up class="h-5 w-5" /></span><strong>DOCUMENTO</strong><small>Subir documentación</small></button>
                <button type="button" x-on:click="showSpotlight = false; {{ $portalType === 'owner' ? "ownerEntryModal = 'incident'" : "employeeEntryModal = 'incident'" }}" class="community-quick-action"><span class="bg-orange-500/15 text-orange-300"><x-heroicon-o-camera class="h-5 w-5" /></span><strong>INCIDENCIA</strong><small>Foto y descripción</small></button>
            </div>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                @if ($portalType === 'employee')
                    <button type="button" x-on:click="showSpotlight = false; openAttendance()" class="community-quick-action"><span class="bg-emerald-500/15 text-emerald-300"><x-heroicon-o-clock class="h-5 w-5" /></span><strong>REGISTRAR SESIÓN</strong><small>Entrada y salida</small></button>
                @else
                    <button type="button" x-on:click="showSpotlight = false; $wire.show('properties')" class="community-quick-action"><span class="bg-emerald-500/15 text-emerald-300"><x-heroicon-o-home-modern class="h-5 w-5" /></span><strong>PROPIEDADES</strong><small>Abrir listado</small></button>
                @endif
                <button type="button" x-on:click="showSpotlight = false; $wire.logout()" wire:confirm="¿Cerrar la sesión de NOVA Community?" class="community-quick-action"><span class="bg-white/10 text-white/60"><x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" /></span><strong>SALIR</strong><small>Cerrar sesión</small></button>
            </div>
            <p class="mt-4 text-center text-[10px] text-white/30">⌘K / Ctrl K o / para abrir · ESC para cerrar</p>
        </section>
    </div>

    @if ($portalType === 'employee')
        @php $todayAttendanceForModal = $attendances->first(fn ($item) => $item->attendance_date?->isToday()); @endphp
        <div x-cloak x-show="attendanceModal" x-transition.opacity class="fixed inset-0 z-[115] flex items-center justify-center bg-black/80 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="attendance-title">
            <button type="button" class="absolute inset-0" x-on:click="resetRecording(); attendanceModal = false" aria-label="Cerrar registro"></button>
            <form
                wire:submit="registerAttendance"
                x-data="{
                    recorder: null,
                    microphoneStream: null,
                    audioChunks: [],
                    audioUrl: null,
                    audioReady: false,
                    recording: false,
                    uploadingAudio: false,
                    microphoneError: '',
                    elapsedSeconds: 0,
                    timer: null,

                    get elapsedLabel() {
                        const minutes = Math.floor(this.elapsedSeconds / 60).toString().padStart(2, '0');
                        const seconds = (this.elapsedSeconds % 60).toString().padStart(2, '0');
                        return `${minutes}:${seconds}`;
                    },

                    async startRecording() {
                        this.microphoneError = '';

                        if (! navigator.mediaDevices?.getUserMedia || ! window.MediaRecorder) {
                            this.microphoneError = 'Este navegador no permite grabar audio desde el formulario.';
                            return;
                        }

                        try {
                            this.microphoneStream = await navigator.mediaDevices.getUserMedia({ audio: true });
                            const preferredType = MediaRecorder.isTypeSupported('audio/webm;codecs=opus')
                                ? 'audio/webm;codecs=opus'
                                : (MediaRecorder.isTypeSupported('audio/mp4') ? 'audio/mp4' : '');
                            this.recorder = preferredType
                                ? new MediaRecorder(this.microphoneStream, { mimeType: preferredType })
                                : new MediaRecorder(this.microphoneStream);
                            this.audioChunks = [];
                            this.audioReady = false;
                            this.elapsedSeconds = 0;
                            this.recorder.ondataavailable = event => {
                                if (event.data.size > 0) this.audioChunks.push(event.data);
                            };
                            this.recorder.onstop = () => this.prepareRecording();
                            this.recorder.start(500);
                            this.recording = true;
                            this.timer = setInterval(() => this.elapsedSeconds++, 1000);
                        } catch (error) {
                            this.microphoneError = 'No se pudo acceder al micrófono. Revisa el permiso del navegador.';
                            this.releaseMicrophone();
                        }
                    },

                    stopRecording() {
                        if (! this.recorder || this.recorder.state === 'inactive') return;
                        this.recorder.stop();
                        this.recording = false;
                        clearInterval(this.timer);
                        this.releaseMicrophone();
                    },

                    prepareRecording() {
                        const mimeType = this.recorder?.mimeType || 'audio/webm';
                        const extension = mimeType.includes('mp4') ? 'm4a' : 'webm';
                        const blob = new Blob(this.audioChunks, { type: mimeType });

                        if (! blob.size) {
                            this.microphoneError = 'La grabación está vacía. Inténtalo de nuevo.';
                            return;
                        }

                        if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                        this.audioUrl = URL.createObjectURL(blob);
                        this.uploadingAudio = true;
                        this.$wire.$upload(
                            'attendanceAudio',
                            new File([blob], `cierre-sesion.${extension}`, { type: mimeType }),
                            () => {
                                this.uploadingAudio = false;
                                this.audioReady = true;
                            },
                            () => {
                                this.uploadingAudio = false;
                                this.audioReady = false;
                                this.microphoneError = 'No se pudo subir la grabación. Vuelve a grabarla.';
                            },
                        );
                    },

                    resetRecording() {
                        if (this.recording) this.stopRecording();
                        this.$wire.set('attendanceAudio', null);
                        this.audioChunks = [];
                        this.audioReady = false;
                        this.uploadingAudio = false;
                        this.elapsedSeconds = 0;
                        this.microphoneError = '';
                        if (this.audioUrl) URL.revokeObjectURL(this.audioUrl);
                        this.audioUrl = null;
                    },

                    releaseMicrophone() {
                        this.microphoneStream?.getTracks().forEach(track => track.stop());
                        this.microphoneStream = null;
                    },
                }"
                x-on:submit="if (recording) { $event.preventDefault(); microphoneError = 'Detén la grabación antes de finalizar la sesión.' }"
                class="community-form-shell relative z-10 w-full max-w-xl overflow-hidden rounded-3xl"
            >
                <header class="flex items-center justify-between border-b border-white/10 bg-[#101419]/90 px-5 py-4 backdrop-blur-xl">
                    <div><h2 id="attendance-title" class="font-bold">{{ $todayAttendanceForModal?->checked_in_at ? 'Finalizar sesión' : 'Iniciar sesión' }}</h2><p class="mt-1 text-xs text-white/45">Se guardarán la hora, la comunidad y tu ubicación actual.</p></div>
                    <button type="button" x-on:click="resetRecording(); attendanceModal = false" class="community-icon-button"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                </header>
                <div class="grid gap-4 p-5">
                    <fieldset class="grid gap-2 text-sm"><legend>Comunidades visitadas <small class="text-white/35">(opcional)</small></legend><div class="grid gap-2 sm:grid-cols-2">@foreach ($employeeCommunities as $community)<label wire:key="attendance-community-{{ $community->id }}" class="flex cursor-pointer items-center gap-3 rounded-xl border border-white/10 bg-white/[0.03] px-4 py-3 transition hover:border-red-500/30"><input wire:model="attendanceCommunityIds" type="checkbox" value="{{ $community->id }}" class="h-4 w-4 rounded border-white/20 bg-black/30 text-red-600 focus:ring-red-500"><span>{{ $community->name }}</span></label>@endforeach</div><small class="text-white/35">Si procede, marca las comunidades en las que has trabajado durante esta jornada.</small>@error('attendanceCommunityIds')<small class="text-red-300">{{ $message }}</small>@enderror @error('attendanceCommunityIds.*')<small class="text-red-300">{{ $message }}</small>@enderror</fieldset>
                    <div class="rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-sm">
                        <div class="flex items-center justify-between gap-3"><span>Ubicación del dispositivo</span><button type="button" x-on:click="openAttendance()" class="community-button community-button-muted text-xs">Actualizar</button></div>
                        <p x-show="locating" class="mt-2 text-blue-300">Obteniendo ubicación precisa…</p>
                        <p x-show="! locating && ! locationError" class="mt-2 text-emerald-300">✓ Ubicación preparada</p>
                        <p x-show="locationError" x-text="locationError" class="mt-2 text-red-300"></p>
                        @error('attendanceLatitude')<small class="mt-2 block text-red-300">Debes permitir la ubicación.</small>@enderror
                    </div>
                    @if ($todayAttendanceForModal?->checked_in_at && ! $todayAttendanceForModal?->checked_out_at)
                        <section class="grid gap-4 rounded-2xl border border-white/10 bg-white/[0.03] p-4 text-sm">
                            <div><span>Nota de audio al finalizar <b class="text-red-400">*</b></span><p class="mt-1 text-xs text-white/35">Graba el resumen de la jornada. Se transcribirá a las notas y se conservará el audio original.</p></div>
                            <div class="flex flex-col items-center gap-4 py-2">
                                <button x-show="! recording && ! audioReady && ! uploadingAudio" type="button" x-on:click="startRecording()" class="group flex h-20 w-20 items-center justify-center rounded-full border border-red-400/30 bg-red-600 shadow-[0_0_35px_rgba(220,38,38,0.22)] transition hover:scale-105 hover:bg-red-500" aria-label="Comenzar grabación">
                                    <x-heroicon-o-microphone class="h-9 w-9 transition group-hover:scale-110" />
                                </button>
                                <button x-show="recording" type="button" x-on:click="stopRecording()" class="relative flex h-20 w-20 items-center justify-center rounded-full border border-red-300/50 bg-red-600 shadow-[0_0_40px_rgba(239,68,68,0.35)]" aria-label="Detener grabación">
                                    <span class="absolute inset-0 animate-ping rounded-full border border-red-400/30"></span><span class="h-7 w-7 rounded-md bg-white"></span>
                                </button>
                                <div x-show="recording" class="text-center"><p class="font-mono text-xl font-bold text-red-300" x-text="elapsedLabel"></p><p class="mt-1 text-xs text-white/40">Grabando… pulsa para detener</p></div>
                                <div x-show="uploadingAudio" class="flex items-center gap-2 text-blue-300"><span class="h-4 w-4 animate-spin rounded-full border-2 border-blue-300/30 border-t-blue-300"></span> Preparando audio…</div>
                                <div x-show="audioReady" class="grid w-full gap-3">
                                    <audio x-bind:src="audioUrl" controls class="h-11 w-full"></audio>
                                    <div class="flex items-center justify-between gap-3"><span class="text-emerald-300">✓ Audio preparado · <span x-text="elapsedLabel"></span></span><button type="button" x-on:click="resetRecording()" class="community-button community-button-muted text-xs"><x-heroicon-o-arrow-path class="h-4 w-4" /> Repetir</button></div>
                                </div>
                            </div>
                            <p x-show="microphoneError" x-text="microphoneError" class="rounded-xl border border-red-500/20 bg-red-500/10 px-3 py-2 text-red-300"></p>
                            @error('attendanceAudio')<small class="text-red-300">{{ $message }}</small>@enderror
                        </section>
                    @endif
                </div>
                <footer class="flex justify-end gap-3 border-t border-white/10 bg-[#101419]/90 px-5 py-4"><button type="button" x-on:click="resetRecording(); attendanceModal = false" class="community-button community-button-muted">Cancelar</button><button type="submit" x-bind:disabled="locating || locationError || uploadingAudio || recording || (@js((bool) $todayAttendanceForModal?->checked_in_at) && ! audioReady)" wire:loading.attr="disabled" wire:target="registerAttendance,attendanceAudio" class="community-button community-button-primary disabled:opacity-50">{{ $todayAttendanceForModal?->checked_in_at ? 'Finalizar sesión' : 'Iniciar sesión' }}</button></footer>
            </form>
        </div>

        <div x-cloak x-show="employeeEntryModal" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-black/80 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="employee-entry-title">
            <button type="button" class="absolute inset-0" x-on:click="employeeEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" aria-label="Cerrar formulario"></button>
            <form
                x-show="employeeEntryModal"
                x-transition
                x-on:submit.prevent="
                    if (employeeEntryModal === 'appointment') $wire.createEmployeeAppointment();
                    if (employeeEntryModal === 'document') $wire.createEmployeeDocument();
                    if (employeeEntryModal === 'ticket') $wire.createEmployeeTicket();
                    if (employeeEntryModal === 'incident') $wire.createEmployeeIncident();
                    if (employeeEntryModal === 'expense') $wire.createEmployeeExpenseTicket();
                "
                class="community-form-shell relative z-10 max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl"
            >
                <header class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#101419]/90 px-5 py-4 backdrop-blur-xl">
                    <div>
                        <h2 id="employee-entry-title" class="font-bold" x-text="employeeEntryModal === 'appointment' ? 'Nueva cita' : employeeEntryModal === 'document' ? 'Subir documento' : employeeEntryModal === 'ticket' ? 'Nuevo ticket' : employeeEntryModal === 'incident' ? 'Nueva incidencia' : 'Ticket de gasto'"></h2>
                        <p class="mt-1 text-xs text-white/45" x-text="employeeEntryModal === 'incident' ? 'Añade una descripción y, si ayuda, una fotografía.' : employeeEntryModal === 'expense' ? 'Adjunta una fotografía legible: reconoceremos sus datos automáticamente.' : 'Completa los datos para enviarlos.'"></p>
                    </div>
                    <button type="button" x-on:click="employeeEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" class="community-icon-button"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                </header>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm sm:col-span-2"><span>Comunidad <small class="text-white/35">(opcional)</small></span><select wire:model.live="employeeCommunityId" class="community-input"><option value="">Sin comunidad específica</option>@foreach ($employeeCommunities as $community)<option value="{{ $community->id }}">{{ $community->name }}</option>@endforeach</select>@error('employeeCommunityId')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <div x-show="employeeEntryModal === 'expense'" class="grid grid-cols-3 gap-2 rounded-2xl border border-white/10 bg-black/20 p-2 sm:col-span-2">
                        @foreach ([['photo', 'FOTO', 'Cámara'], ['ocr', 'OCR', 'Subir recibo'], ['manual', 'MANUAL', 'Introducir datos']] as [$mode, $label, $description])
                            <button type="button" wire:key="expense-mode-{{ $mode }}" wire:click="selectExpenseInputMode('{{ $mode }}')" class="rounded-xl border px-2 py-3 text-center transition {{ $expenseInputMode === $mode ? 'border-red-400/50 bg-red-500/15 text-white shadow-[0_0_25px_rgba(220,38,38,0.12)]' : 'border-white/5 bg-white/[0.03] text-white/50 hover:border-white/20 hover:bg-white/[0.06]' }}">
                                <strong class="block text-xs tracking-[0.14em]">{{ $label }}</strong>
                                <small class="mt-1 block text-[10px]">{{ $description }}</small>
                            </button>
                        @endforeach
                    </div>

                    <label x-show="employeeEntryModal === 'incident'" class="grid gap-2 text-sm sm:col-span-2"><span>Orden relacionada <small class="text-white/35">(opcional)</small></span><select wire:model="incidentWorkOrderId" class="community-input"><option value="">Sin orden relacionada</option>@foreach ($workOrders as $order)<option value="{{ $order->id }}">{{ $order->code }} · {{ $order->community?->name }}</option>@endforeach</select>@error('incidentWorkOrderId')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal === 'incident'" class="grid gap-2 text-sm"><span>Tipo de servicio <b class="text-red-400">*</b></span><select wire:model.live="incidentWorkCategoryId" class="community-input"><option value="">Selecciona un tipo</option>@foreach ($workCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('incidentWorkCategoryId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="employeeEntryModal === 'incident'" class="grid gap-2 text-sm"><span>Servicio <small class="text-white/35">(opcional)</small></span><select wire:model="incidentWorkCatalogId" class="community-input" @disabled(! $incidentWorkCategoryId)><option value="">{{ $incidentWorkCategoryId ? 'Sin servicio concreto' : 'Selecciona primero el tipo' }}</option>@foreach ($workCatalogs as $service)<option value="{{ $service->id }}">{{ $service->title }}</option>@endforeach</select>@error('incidentWorkCatalogId')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal !== 'expense' || $wire.expenseInputMode === 'manual'" class="grid gap-2 text-sm sm:col-span-2"><span><span x-text="employeeEntryModal === 'appointment' ? 'Motivo' : employeeEntryModal === 'incident' ? 'Tipo de incidencia' : employeeEntryModal === 'expense' ? 'Concepto del gasto' : 'Título'"></span><b x-show="employeeEntryModal === 'expense' && $wire.expenseInputMode === 'manual'" class="ml-1 text-red-400">*</b></span><input wire:model="entryTitle" class="community-input" x-bind:placeholder="employeeEntryModal === 'appointment' ? 'Motivo de la cita' : 'Título breve y claro'">@error('entryTitle')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal === 'appointment'" class="grid gap-2 text-sm"><span>Día <b class="text-red-400">*</b></span><input wire:model.live="appointmentDate" type="date" min="{{ today()->format('Y-m-d') }}" class="community-input">@error('appointmentDate')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="employeeEntryModal === 'appointment'" class="grid gap-2 text-sm"><span>Hora disponible <b class="text-red-400">*</b></span><select wire:model="appointmentTime" class="community-input"><option value="">Selecciona una hora</option>@foreach ($appointmentSlots as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@if ($appointmentDate && $employeeCommunityId && $appointmentSlots === [])<small class="text-amber-300">No hay horas disponibles para este día.</small>@endif @error('appointmentTime')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal === 'incident'" class="grid gap-2 text-sm sm:col-span-2"><span>Prioridad</span><select wire:model="incidentPriority" class="community-input"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>@error('incidentPriority')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal === 'expense' && $wire.expenseInputMode === 'manual'" class="grid gap-2 text-sm sm:col-span-2"><span>Importe <b class="text-red-400">*</b></span><div class="relative"><input wire:model="expenseAmount" type="number" min="0.01" step="0.01" class="community-input pr-10" placeholder="0,00"><span class="absolute right-4 top-3 text-white/40">€</span></div>@error('expenseAmount')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="employeeEntryModal !== 'appointment' && (employeeEntryModal !== 'expense' || $wire.expenseInputMode === 'manual')" class="grid gap-2 text-sm sm:col-span-2"><span>Descripción <b x-show="['ticket', 'incident'].includes(employeeEntryModal)" class="text-red-400">*</b></span><textarea wire:model="entryDescription" rows="4" class="community-input resize-none" placeholder="Describe la solicitud con detalle"></textarea>@error('entryDescription')<small class="text-red-300">{{ $message }}</small>@enderror</label>

                    <label x-show="['document', 'ticket'].includes(employeeEntryModal) || (employeeEntryModal === 'expense' && $wire.expenseInputMode !== 'photo')" class="grid gap-2 text-sm sm:col-span-2"><span x-text="['document', 'ticket'].includes(employeeEntryModal) ? 'Archivo (PDF o imagen)' : $wire.expenseInputMode === 'manual' ? 'Imagen del recibo (opcional)' : 'Recibo para reconocer'"></span><input wire:model="entryFile" type="file" x-bind:accept="['document', 'ticket'].includes(employeeEntryModal) ? 'application/pdf,image/jpeg,image/png,image/webp' : 'image/jpeg,image/png,image/webp'" class="community-input file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white"><small class="text-white/35">Máximo 5 MB para fotos y 10 MB para documentos.</small><span wire:loading wire:target="entryFile" class="text-xs text-blue-300">Subiendo archivo…</span>@error('entryFile')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <div x-show="employeeEntryModal === 'incident'" class="contents"><x-community-camera-capture /></div>
                    <div x-show="employeeEntryModal === 'expense' && $wire.expenseInputMode === 'photo'" class="contents"><x-community-camera-capture label="Recibo" :required="true" filename-prefix="recibo" /></div>

                    @if ($receiptOcrData !== [])
                        <section x-show="employeeEntryModal === 'expense' && $wire.expenseInputMode !== 'manual'" class="grid gap-3 rounded-2xl border border-emerald-400/20 bg-emerald-500/[0.07] p-4 text-sm sm:col-span-2">
                            <div class="flex items-center justify-between"><strong class="text-emerald-300">✓ Recibo reconocido</strong><small class="text-white/40">Revisa antes de enviar</small></div>
                            <div class="grid grid-cols-2 gap-3 text-xs"><div><span class="text-white/40">Comercio</span><p class="mt-1 font-semibold">{{ $receiptOcrData['empresa'] ?? 'No detectado' }}</p></div><div><span class="text-white/40">Fecha</span><p class="mt-1 font-semibold">{{ $receiptOcrData['fecha'] ?? 'No detectada' }}</p></div><div><span class="text-white/40">Concepto</span><p class="mt-1 font-semibold">{{ $receiptOcrData['concepto'] ?? 'No detectado' }}</p></div><div><span class="text-white/40">Total</span><p class="mt-1 text-lg font-bold text-emerald-300">{{ isset($receiptOcrData['total']) ? number_format((float) $receiptOcrData['total'], 2, ',', '.') . ' €' : 'No detectado' }}</p></div></div>
                        </section>
                    @endif

                    <div x-show="employeeEntryModal === 'expense' && $wire.expenseInputMode !== 'manual'" class="sm:col-span-2">
                        <div wire:loading.flex wire:target="entryFile" class="items-center justify-center gap-3 rounded-2xl border border-blue-400/20 bg-blue-500/10 px-4 py-5 text-sm text-blue-200">
                            <span class="h-5 w-5 animate-spin rounded-full border-2 border-blue-200/25 border-t-blue-200"></span>
                            <strong>Reconociendo recibo…</strong>
                        </div>
                    </div>
                </div>
                <footer class="sticky bottom-0 flex justify-end gap-3 border-t border-white/10 bg-[#101419]/90 px-5 py-4 backdrop-blur-xl">
                    <button type="button" x-on:click="employeeEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" class="community-button community-button-muted">Cancelar</button>
                    <button type="submit" class="community-button community-button-primary" wire:loading.attr="disabled">Enviar</button>
                </footer>
            </form>
        </div>
    @endif

    @if ($portalType === 'owner')
        <div x-cloak x-show="ownerEntryModal" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-black/80 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="owner-entry-title">
            <button type="button" class="absolute inset-0" x-on:click="ownerEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" aria-label="Cerrar formulario"></button>
            <form
                x-show="ownerEntryModal"
                x-transition
                x-on:submit.prevent="
                    if (ownerEntryModal === 'appointment') $wire.createOwnerAppointment();
                    if (ownerEntryModal === 'document') $wire.createOwnerDocument();
                    if (ownerEntryModal === 'incident') $wire.createOwnerIncident();
                "
                class="community-form-shell relative z-10 max-h-[92vh] w-full max-w-2xl overflow-y-auto rounded-3xl"
            >
                <header class="sticky top-0 z-10 flex items-center justify-between border-b border-white/10 bg-[#101419]/90 px-5 py-4 backdrop-blur-xl">
                    <div><h2 id="owner-entry-title" class="font-bold" x-text="ownerEntryModal === 'appointment' ? 'Nueva cita' : ownerEntryModal === 'document' ? 'Subir documento' : 'Nueva incidencia'"></h2><p class="mt-1 text-xs text-white/45" x-text="ownerEntryModal === 'incident' ? 'Describe el problema y adjunta una fotografía.' : 'Selecciona la propiedad y completa los datos.'"></p></div>
                    <button type="button" x-on:click="ownerEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" class="community-icon-button"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                </header>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label x-show="ownerEntryModal === 'appointment'" class="grid gap-2 text-sm sm:col-span-2"><span>Comunidad <b class="text-red-400">*</b></span><select wire:model.live="ownerCommunityId" class="community-input"><option value="">Selecciona una comunidad</option>@foreach ($person->communities as $community)<option value="{{ $community->id }}">{{ $community->name }}</option>@endforeach</select>@error('ownerCommunityId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal !== 'appointment'" class="grid gap-2 text-sm sm:col-span-2"><span>Propiedad <b class="text-red-400">*</b></span><select wire:model="ticketPropertyId" class="community-input"><option value="">Selecciona una propiedad</option>@foreach ($person->properties as $property)<option value="{{ $property->id }}">{{ $property->name }} · {{ $property->community?->name }}</option>@endforeach</select>@error('ticketPropertyId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'incident'" class="grid gap-2 text-sm"><span>Tipo de servicio <b class="text-red-400">*</b></span><select wire:model.live="incidentWorkCategoryId" class="community-input"><option value="">Selecciona un tipo</option>@foreach ($workCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach</select>@error('incidentWorkCategoryId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'incident'" class="grid gap-2 text-sm"><span>Servicio <small class="text-white/35">(opcional)</small></span><select wire:model="incidentWorkCatalogId" class="community-input" @disabled(! $incidentWorkCategoryId)><option value="">{{ $incidentWorkCategoryId ? 'Sin servicio concreto' : 'Selecciona primero el tipo' }}</option>@foreach ($workCatalogs as $service)<option value="{{ $service->id }}">{{ $service->title }}</option>@endforeach</select>@error('incidentWorkCatalogId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label class="grid gap-2 text-sm sm:col-span-2"><span x-text="ownerEntryModal === 'appointment' ? 'Motivo' : ownerEntryModal === 'incident' ? 'Tipo de incidencia' : 'Título'"></span><input wire:model="entryTitle" class="community-input" x-bind:placeholder="ownerEntryModal === 'appointment' ? 'Motivo de la cita' : 'Título breve y claro'">@error('entryTitle')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'appointment'" class="grid gap-2 text-sm"><span>Día <b class="text-red-400">*</b></span><input wire:model.live="appointmentDate" type="date" min="{{ today()->format('Y-m-d') }}" class="community-input">@error('appointmentDate')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'appointment'" class="grid gap-2 text-sm"><span>Hora disponible <b class="text-red-400">*</b></span><select wire:model="appointmentTime" class="community-input"><option value="">Selecciona una hora</option>@foreach ($appointmentSlots as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>@if ($appointmentDate && $ownerCommunityId && $appointmentSlots === [])<small class="text-amber-300">No hay horas disponibles para este día.</small>@endif @error('appointmentTime')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'incident'" class="grid gap-2 text-sm sm:col-span-2"><span>Prioridad</span><select wire:model="incidentPriority" class="community-input"><option value="low">Baja</option><option value="normal">Normal</option><option value="high">Alta</option><option value="urgent">Urgente</option></select>@error('incidentPriority')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal !== 'appointment'" class="grid gap-2 text-sm sm:col-span-2"><span>Descripción <b x-show="ownerEntryModal === 'incident'" class="text-red-400">*</b></span><textarea wire:model="entryDescription" rows="4" class="community-input resize-none" placeholder="Añade la información necesaria"></textarea>@error('entryDescription')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label x-show="ownerEntryModal === 'document'" class="grid gap-2 text-sm sm:col-span-2"><span>Archivo (PDF o imagen)</span><input wire:model="entryFile" type="file" accept="application/pdf,image/jpeg,image/png,image/webp" class="community-input file:mr-3 file:rounded-lg file:border-0 file:bg-white/10 file:px-3 file:py-2 file:text-xs file:font-bold file:text-white"><span wire:loading wire:target="entryFile" class="text-xs text-blue-300">Subiendo archivo…</span>@error('entryFile')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <div x-show="ownerEntryModal === 'incident'" class="contents"><x-community-camera-capture /></div>
                </div>
                <footer class="sticky bottom-0 flex justify-end gap-3 border-t border-white/10 bg-[#101419]/90 px-5 py-4 backdrop-blur-xl"><button type="button" x-on:click="ownerEntryModal = null; window.dispatchEvent(new CustomEvent('community-camera-reset'))" class="community-button community-button-muted">Cancelar</button><button type="submit" class="community-button community-button-primary" wire:loading.attr="disabled">Enviar</button></footer>
            </form>
        </div>
    @endif

    @if ($portalType === 'owner')
        <div x-cloak x-show="showTicketModal" x-transition.opacity class="fixed inset-0 z-[110] flex items-center justify-center bg-black/80 p-3 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="new-community-ticket">
            <button type="button" class="absolute inset-0" x-on:click="showTicketModal = false" aria-label="Cerrar formulario"></button>
            <form wire:submit="createTicket" x-show="showTicketModal" x-transition class="community-form-shell relative z-10 w-full max-w-2xl overflow-hidden rounded-3xl">
                <header class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div><h2 id="new-community-ticket" class="font-bold">Nuevo ticket</h2><p class="mt-1 text-xs text-white/45">Envía una consulta o incidencia a la comunidad.</p></div>
                    <button type="button" x-on:click="showTicketModal = false" class="community-icon-button"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                </header>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label class="grid gap-2 text-sm"><span>Propiedad <b class="text-red-400">*</b></span><select wire:model="ticketPropertyId" class="community-input"><option value="">Selecciona una propiedad</option>@foreach ($person->properties as $property)<option value="{{ $property->id }}">{{ $property->name }}</option>@endforeach</select>@error('ticketPropertyId')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label class="grid gap-2 text-sm"><span>Asunto <b class="text-red-400">*</b></span><input wire:model="ticketTitle" class="community-input" placeholder="Describe brevemente la solicitud">@error('ticketTitle')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                    <label class="grid gap-2 text-sm sm:col-span-2"><span>Descripción <b class="text-red-400">*</b></span><textarea wire:model="ticketDescription" rows="5" class="community-input resize-none" placeholder="Añade toda la información necesaria"></textarea>@error('ticketDescription')<small class="text-red-300">{{ $message }}</small>@enderror</label>
                </div>
                <footer class="flex justify-end gap-3 border-t border-white/10 px-5 py-4">
                    <button type="button" x-on:click="showTicketModal = false" class="community-button community-button-muted">Cancelar</button>
                    <button type="submit" class="community-button community-button-primary" wire:loading.attr="disabled" wire:target="createTicket"><span wire:loading.remove wire:target="createTicket">Crear ticket</span><span wire:loading wire:target="createTicket">Creando…</span></button>
                </footer>
            </form>
        </div>
    @endif

    @if ($detailType && $detailId)
        @php
            $detailRecord = match ([$portalType, $detailType]) {
                ['owner', 'document'] => $documents->firstWhere('id', $detailId),
                ['owner', 'appointment'] => $appointments->firstWhere('id', $detailId),
                ['owner', 'ticket'] => $tickets->firstWhere('id', $detailId),
                ['owner', 'fee'] => $fees->firstWhere('id', $detailId),
                ['owner', 'incident'] => $ownerIncidents->firstWhere('id', $detailId),
                ['employee', 'document'] => $employeeDocuments->firstWhere('id', $detailId),
                ['employee', 'plan'] => $plans->firstWhere('id', $detailId),
                ['employee', 'appointment'] => $employeeAppointments->firstWhere('id', $detailId),
                ['employee', 'ticket'] => $employeeTickets->concat($tickets)->firstWhere('id', $detailId),
                ['employee', 'incident'] => $incidents->firstWhere('id', $detailId),
                default => null,
            };
            $detailTitle = $detailRecord?->title ?? $detailRecord?->concept ?? 'Detalle';
            $detailDescription = $detailRecord?->description ?? data_get($detailRecord?->metadata, 'description');
            $detailAttachment = match (true) {
                $detailType === 'document' => $detailRecord?->path,
                $detailType === 'plan' => $detailRecord?->path,
                $detailType === 'incident', $portalType === 'owner' && $detailType === 'incident' => $detailRecord?->photos?->first()?->path,
                $portalType === 'employee' && $detailType === 'incident' => $detailRecord?->photos?->first()?->path,
                default => null,
            };
        @endphp

        <div class="fixed inset-0 z-[120] flex items-center justify-center bg-black/85 p-3 backdrop-blur-md" role="dialog" aria-modal="true" aria-labelledby="community-detail-title">
            <button type="button" wire:click="closeDetail" class="absolute inset-0" aria-label="Cerrar detalle"></button>
            <section class="community-detail-shell relative z-10 max-h-[92vh] w-full max-w-3xl overflow-y-auto rounded-[1.75rem] p-3 sm:p-5">
                <header class="sticky top-0 z-10 mb-4 flex items-center justify-between rounded-full border border-white/10 bg-black/35 px-4 py-2 backdrop-blur-xl">
                    <div class="flex items-center gap-2"><span class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/55">{{ strtoupper($detailType) }}</span><span class="text-white/25">›</span><span class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/80">Detalle</span></div>
                    <button type="button" wire:click="closeDetail" class="community-icon-button"><x-heroicon-o-x-mark class="h-5 w-5" /></button>
                </header>

                @if ($detailRecord)
                    <div class="mb-4 px-2"><h2 id="community-detail-title" class="truncate text-xl font-semibold text-white/90">{{ $detailTitle }}</h2><p class="mt-1 text-sm text-white/40">{{ $detailRecord->created_at?->format('d/m/Y H:i') }}</p></div>
                    <div class="community-detail-surface grid gap-5 rounded-3xl p-5 sm:grid-cols-2 sm:p-6">
                        <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Estado</p><p class="mt-2 font-semibold text-emerald-300">{{ strtoupper($detailRecord->status ?? 'activo') }}</p></article>
                        <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Contexto</p><p class="mt-2 font-semibold">{{ $detailRecord->property?->name ?? $detailRecord->community?->name ?? 'NOVA Community' }}</p></article>

                        @if ($detailType === 'appointment')
                            <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 sm:col-span-2"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Fecha y hora</p><p class="mt-2 text-lg font-semibold text-blue-300">{{ $detailRecord->starts_at?->format('d/m/Y H:i') }}</p></article>
                        @elseif ($detailType === 'fee')
                            <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Periodo</p><p class="mt-2 font-semibold">{{ $detailRecord->period?->format('m/Y') }}</p></article><article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Importe</p><p class="mt-2 text-lg font-bold text-amber-300">{{ number_format((float) $detailRecord->amount, 2, ',', '.') }} €</p></article>
                        @elseif (in_array($detailType, ['ticket', 'incident'], true))
                            <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">ID</p><p class="mt-2 font-semibold">#{{ $detailRecord->id }}</p></article><article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Prioridad</p><p class="mt-2 font-semibold text-amber-300">{{ strtoupper($detailRecord->priority ?? 'normal') }}</p></article>
                        @endif

                        @if ($detailDescription)
                            <article class="rounded-2xl border border-white/10 bg-white/[0.035] p-4 sm:col-span-2"><p class="text-[10px] font-bold uppercase tracking-[0.16em] text-white/35">Descripción</p><p class="mt-2 whitespace-pre-line text-sm leading-relaxed text-white/70">{{ $detailDescription }}</p></article>
                        @endif

                        @if ($detailAttachment)
                                                    <img src="{{ asset('storage/'.$detailAttachment) }}"  class="community-button community-button-primary sm:col-span-2"><x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> 

                            <a href="{{ asset('storage/'.$detailAttachment) }}" target="_blank" rel="noopener" class="community-button community-button-primary sm:col-span-2"><x-heroicon-o-arrow-top-right-on-square class="h-4 w-4" /> Abrir archivo</a>
                        @endif
                    </div>
                @else
                    <div class="p-6"><x-community-portal-empty text="El registro ya no está disponible" /></div>
                @endif
            </section>
        </div>
    @endif

    @include('livewire.community-portal-onboarding', ['portalType' => $portalType])

    <style>
        [x-cloak] { display: none !important; }
        [data-community-portal] .community-glass { position: relative; border: 1px solid rgb(255 255 255 / .14); background: rgb(255 255 255 / .05); box-shadow: 0 20px 50px rgb(0 0 0 / .45), inset 0 1px rgb(255 255 255 / .08); backdrop-filter: blur(22px) saturate(125%); -webkit-backdrop-filter: blur(22px) saturate(125%); }
        [data-community-portal] .community-topbar { position: sticky; top: .5rem; z-index: 40; display: grid; min-height: 3.25rem; grid-template-columns: 2.5rem minmax(0, 1fr) auto; align-items: center; gap: .4rem; overflow: visible; border: 1px solid rgb(255 255 255 / .12); border-radius: 1.05rem; background: rgb(7 9 13 / .82); padding: .35rem .42rem; box-shadow: 0 18px 48px rgb(0 0 0 / .42), inset 0 1px rgb(255 255 255 / .07); backdrop-filter: blur(28px) saturate(135%); -webkit-backdrop-filter: blur(28px) saturate(135%); }
        [data-community-portal] .community-topbar::before { position: absolute; inset: 0; border-radius: inherit; background: linear-gradient(115deg, rgb(255 255 255 / .055), transparent 34%, transparent 72%, rgb(255 255 255 / .025)); content: ''; pointer-events: none; }
        [data-community-portal] .community-topbar__edge, [data-community-portal] .community-topbar__icon { position: relative; z-index: 1; display: inline-flex; height: 2.35rem; width: 2.35rem; align-items: center; justify-content: center; border-radius: .85rem; color: rgb(255 255 255 / .82); transition: color .2s ease, background-color .2s ease, transform .2s ease; }
        [data-community-portal] .community-topbar__edge:hover, [data-community-portal] .community-topbar__icon:hover { background: rgb(255 255 255 / .075); color: white; transform: translateY(-1px); }
        [data-community-portal] .community-topbar__brand { position: relative; z-index: 1; display: inline-flex; min-width: 0; align-items: center; justify-content: flex-start; gap: .48rem; justify-self: start; }
        [data-community-portal] .community-topbar__brand span { border-left: 1px solid rgb(255 255 255 / .16); padding-left: .5rem; font-size: .56rem; font-weight: 800; letter-spacing: .19em; color: rgb(255 255 255 / .47); }
        [data-community-portal] .community-topbar__actions { position: relative; z-index: 1; display: flex; align-items: center; gap: .05rem; }
        [data-community-portal] .community-topbar__avatar { position: relative; z-index: 1; display: inline-flex; height: 2.25rem; min-width: 2.25rem; align-items: center; justify-content: center; border-radius: 999px; background: #dc101c; padding-inline: .45rem; font-size: .78rem; font-weight: 800; color: white; box-shadow: 0 0 0 4px rgb(220 16 28 / .09); transition: transform .2s ease, background-color .2s ease; }
        [data-community-portal] .community-topbar__avatar:hover { transform: scale(1.04); background: #ef1725; }
        [data-community-portal] .community-topbar__badge { position: absolute; right: -.12rem; top: -.18rem; min-width: 1rem; border: 1px solid rgb(248 113 113 / .45); border-radius: 999px; background: #4a2024; padding: .05rem .22rem; font-size: .58rem; font-weight: 700; line-height: .85rem; color: #fecaca; }
        @media (min-width: 480px) { [data-community-portal] .community-topbar { min-height: 3.55rem; grid-template-columns: 2.65rem minmax(0, 1fr) auto; padding-inline: .55rem; } [data-community-portal] .community-topbar__brand { justify-self: center; } [data-community-portal] .community-topbar__brand span { font-size: .6rem; } }
        [data-community-portal] article, [data-community-portal] section:not([role="dialog"]) { box-shadow: inset 0 1px 0 rgb(255 255 255 / .035), 0 14px 35px rgb(0 0 0 / .15); transition: transform .2s ease, border-color .2s ease, background-color .2s ease, box-shadow .2s ease; }
        [data-community-portal] article:hover, [data-community-portal] a:hover { transform: translateY(-2px); border-color: rgb(255 255 255 / .23); box-shadow: 0 18px 42px rgb(0 0 0 / .28), inset 0 1px 0 rgb(255 255 255 / .06); }
        [data-community-portal] .community-icon-button { display: inline-flex; height: 2.5rem; width: 2.5rem; align-items: center; justify-content: center; border-radius: .85rem; border: 1px solid rgb(255 255 255 / .12); background: rgb(255 255 255 / .045); color: rgb(255 255 255 / .75); transition: .2s ease; }
        [data-community-portal] .community-icon-button:hover { border-color: rgb(239 68 68 / .55); background: rgb(239 68 68 / .10); color: white; transform: translateY(-1px); }
        [data-community-portal] .community-button { display: inline-flex; min-height: 2.5rem; align-items: center; justify-content: center; gap: .45rem; border-radius: .8rem; padding: .55rem .9rem; font-size: .8rem; font-weight: 700; transition: transform .2s ease, background-color .2s ease, border-color .2s ease; }
        [data-community-portal] .community-button:hover { transform: translateY(-1px); }
        [data-community-portal] .community-button-primary { border: 1px solid rgb(248 113 113 / .3); background: #c9232e; color: white; box-shadow: 0 8px 24px rgb(185 28 28 / .24); }
        [data-community-portal] .community-button-primary:hover { background: #dc2734; }
        [data-community-portal] .community-button-muted { border: 1px solid rgb(255 255 255 / .14); background: rgb(255 255 255 / .055); color: rgb(255 255 255 / .8); }
        [data-community-portal] .community-button-muted:hover { border-color: rgb(255 255 255 / .28); background: rgb(255 255 255 / .09); color: white; }
        [data-community-portal] .community-input { width: 100%; border-radius: .8rem; border: 1px solid rgb(255 255 255 / .14); background: rgb(255 255 255 / .055); padding: .7rem .85rem; color: white; outline: none; transition: border-color .2s ease, box-shadow .2s ease, background-color .2s ease; color-scheme: dark; }
        [data-community-portal] .community-input:focus { border-color: rgb(239 68 68 / .65); background: rgb(255 255 255 / .075); box-shadow: 0 0 0 3px rgb(239 68 68 / .12); }
        [data-community-portal] .community-input::placeholder { color: rgb(255 255 255 / .3); }
        [data-community-portal] .community-quick-action { display: grid; grid-template-columns: auto 1fr; align-items: center; gap: .15rem .75rem; border-radius: 1rem; border: 1px solid rgb(255 255 255 / .1); background: rgb(0 0 0 / .22); padding: .8rem; text-align: left; transition: .2s ease; }
        [data-community-portal] .community-quick-action:hover { transform: translateY(-2px); border-color: rgb(255 255 255 / .25); background: rgb(255 255 255 / .055); }
        [data-community-portal] .community-quick-action > span { grid-row: span 2; display: inline-flex; height: 2.65rem; width: 2.65rem; align-items: center; justify-content: center; border-radius: .8rem; }
        [data-community-portal] .community-quick-action strong { font-size: .72rem; letter-spacing: .08em; }
        [data-community-portal] .community-quick-action small { color: rgb(255 255 255 / .4); }
        [data-community-portal] .community-portal-row { position: relative; overflow: hidden; border-top: 1px solid rgb(255 255 255 / .13); border-right: 1px solid rgb(255 255 255 / .13); border-bottom: 1px solid rgb(255 255 255 / .13); border-radius: 1.4rem; background: linear-gradient(145deg, rgb(255 255 255 / .075), rgb(255 255 255 / .025)); box-shadow: inset 0 1px 0 rgb(255 255 255 / .05), 0 16px 40px rgb(0 0 0 / .24); backdrop-filter: blur(20px) saturate(125%); transition: translate .22s ease, scale .22s ease, border-color .22s ease, background-color .22s ease, box-shadow .22s ease; }
        [data-community-portal] .community-portal-row::after { position: absolute; inset: 0; border-radius: inherit; background: radial-gradient(circle at var(--row-x, 50%) var(--row-y, 0%), rgb(255 255 255 / .09), transparent 42%); opacity: 0; content: ''; pointer-events: none; transition: opacity .22s ease; }
        [data-community-portal] .community-portal-row:hover { translate: 0 -2px; scale: 1.004; border-top-color: rgb(255 255 255 / .23); border-right-color: rgb(255 255 255 / .23); border-bottom-color: rgb(255 255 255 / .23); background: linear-gradient(145deg, rgb(255 255 255 / .10), rgb(255 255 255 / .04)); box-shadow: inset 0 1px 0 rgb(255 255 255 / .07), 0 22px 48px rgb(0 0 0 / .34); }
        [data-community-portal] .community-portal-row:hover::after { opacity: 1; }
        [data-community-portal] .community-portal-row:focus-visible { outline: 2px solid rgb(96 165 250 / .65); outline-offset: 3px; }
        [data-community-portal] .community-detail-shell, [data-community-portal] .community-form-shell { border: 1px solid rgb(255 255 255 / .17); background: radial-gradient(circle at 88% 5%, rgb(185 28 28 / .11), transparent 26%), linear-gradient(145deg, rgb(26 29 33 / .97), rgb(9 12 15 / .98)); box-shadow: inset 0 1px 0 rgb(255 255 255 / .07), 0 30px 90px rgb(0 0 0 / .58); backdrop-filter: blur(28px) saturate(130%); }
        [data-community-portal] .community-detail-surface, [data-community-portal] .community-form-shell > .grid { margin: 1rem; border: 1px solid rgb(255 255 255 / .16); background: linear-gradient(145deg, rgb(255 255 255 / .085), rgb(255 255 255 / .035)); box-shadow: inset 0 1px 0 rgb(255 255 255 / .055), 0 20px 50px rgb(0 0 0 / .28); backdrop-filter: blur(22px); }
        [data-community-portal] .community-form-shell > .grid { border-radius: 1.35rem; }
        [data-community-portal] .community-detail-surface article { border-color: transparent; background: transparent; box-shadow: none; padding: 0; }
        [data-community-portal] .community-detail-surface article:hover { transform: none; border-color: transparent; box-shadow: none; }
    </style>
</div>
