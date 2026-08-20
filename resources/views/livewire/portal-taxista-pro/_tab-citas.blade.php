{{-- CITAS --}}
<div>
    <div class="sticky top-0 z-10 -mx-2 px-2 pt-1 pb-3 backdrop-blur-md">
        <div class="flex items-center justify-between gap-3">
            <div class="min-w-0">
                <div class="inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                    <a
                        href="{{ route('mobile-portal') }}"
                        class="inline-flex items-center gap-2 text-xs font-semibold uppercase tracking-widest text-white/85 hover:text-white"
                    >
                        <x-heroicon-o-chevron-left class="h-4 w-4"/>
                        Citas
                    </a>
                </div>
            </div>

            <div class="tl-segment-group flex items-center gap-1.5 shrink-0">
                <button
                    type="button"
                    class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                    x-bind:class="showCitasTopFilters ? 'ring-1 ring-blue-400/40 text-blue-200' : ''"
                    x-bind:aria-label="showCitasTopFilters ? 'Ocultar opciones' : 'Mostrar opciones'"
                    x-on:click="showCitasTopFilters = !showCitasTopFilters"
                >
                    <x-heroicon-o-adjustments-horizontal class="h-4 w-4"/>
                </button>

                <button
                    type="button"
                    class="glass-hover inline-flex h-9 w-9 items-center justify-center rounded-full bg-blue-500/20 text-blue-100 ring-1 ring-blue-500/30"
                    wire:click="mountAction('createCita')"
                    aria-label="Nueva cita"
                >
                    <x-heroicon-o-plus class="h-4 w-4"/>
                </button>
            </div>
        </div>

        <div class="mt-3 flex gap-2 overflow-x-auto pb-1" x-show="showCitasTopFilters" x-transition.opacity
             style="display: none;">
            <button type="button" wire:click="toggleCitasFilter('upcoming')"
                    class="tl-pill tl-segment {{ $citasFilterUpcoming ? 'tl-segment-active ring-1 ring-blue-400/30 bg-blue-500/10 text-blue-200' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Próximas
            </button>
            <button type="button" wire:click="toggleCitasFilter('pendiente')"
                    class="tl-pill tl-segment {{ $citasFilterPendiente ? 'tl-segment-active ring-1 ring-amber-400/30 bg-amber-500/10 text-amber-200' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Pendientes
            </button>
            <button type="button" wire:click="toggleCitasFilter('confirmada')"
                    class="tl-pill tl-segment {{ $citasFilterConfirmada ? 'tl-segment-active  ring-1 ring-emerald-400/30 bg-emerald-500/10 text-emerald-200' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Confirmadas
            </button>
            <button type="button" wire:click="toggleCitasFilter('all')"
                    class="tl-pill tl-segment {{ $citasFilterAll ? 'tl-segment-active ring-1 ring-white/15 bg-white/10' : 'tl-pill-zinc' }} px-4 py-1.5 text-xs font-medium">
                Todas
            </button>
        </div>

        <p class="mt-2 text-[12px] text-white/60" x-show="showCitasTopFilters" x-transition.opacity
           style="display: none;">
            {{ count($citas ?? []) }} citas
            <span class="text-white/35">·</span>
            <span class="text-white/45">
                {{ $citasFilterAll ? 'Todas' : 'Filtro personalizado' }}
            </span>
        </p>
    </div>

    <div class="mt-4">
        @php
            $estadoLabel = static function (string $status): string {
                return match ($status) {
                    'confirmada' => 'Confirmada',
                    'pendiente'  => 'Pendiente',
                    'cancelada'  => 'Cancelada',
                    'finalizada' => 'Finalizada',
                    default      => ucfirst($status),
                };
            };

            $iconBgByEstado = static function (string $status): string {
                return match ($status) {
                    'confirmada' => 'bg-emerald-500/10 ring-1 ring-emerald-500/20',
                    'pendiente'  => 'bg-amber-500/10 ring-1 ring-amber-500/20',
                    'cancelada'  => 'bg-red-500/10 ring-1 ring-red-500/20',
                    default      => 'bg-blue-500/10 ring-1 ring-blue-500/20',
                };
            };

            $iconColorByEstado = static function (string $status): string {
                return match ($status) {
                    'confirmada' => 'text-emerald-300',
                    'pendiente'  => 'text-amber-300',
                    'cancelada'  => 'text-red-300',
                    default      => 'text-blue-200',
                };
            };

            $appointmentBadgeColor = static function (string $status): string {
                return match ($status) {
                    'confirmada' => 'emerald',
                    'pendiente'  => 'amber',
                    'cancelada'  => 'red',
                    'finalizada' => 'blue',
                    default      => 'zinc',
                };
            };

            $departmentFallbackColor = static function (?string $department): string {
                if (blank($department) || $department === '—') {
                    return '#71717a';
                }

                $palette = ['#8b5cf6', '#3b82f6', '#10b981', '#f59e0b'];

                return $palette[crc32((string) $department) % count($palette)];
            };

            $resolveDepartmentColor = static function (?string $rawColor): ?string {
                $value = strtolower(trim((string) $rawColor));

                if ($value === '') {
                    return null;
                }

                $named = [
                    'red' => '#ef4444',
                    'blue' => '#3b82f6',
                    'amber' => '#f59e0b',
                    'emerald' => '#10b981',
                    'violet' => '#8b5cf6',
                    'zinc' => '#71717a',
                ];

                if (array_key_exists($value, $named)) {
                    return $named[$value];
                }

                if (preg_match('/^#[0-9a-f]{3}([0-9a-f]{3})?$/i', $value) === 1) {
                    return $value;
                }

                if (preg_match('/^[0-9a-f]{3}([0-9a-f]{3})?$/i', $value) === 1) {
                    return '#'.$value;
                }

                return null;
            };
        @endphp

        @if (count($citas) === 0)
            <x-portal.card padding="p-6">
                <p class="text-white/60">Sin citas registradas.</p>
                <div class="mt-4">
                    <x-portal.button variant="primary" x-on:click="runCreateAction('createCita')" x-bind:disabled="pendingAction === 'create:createCita'">+ Solicitar cita
                    </x-portal.button>
                </div>
            </x-portal.card>
        @else
            <div class="space-y-3">
                @foreach ($citas as $cita)
                    @php
                        $citaUrl  = $cita['url'] ?? '#';
                        $estado   = (string) ($cita['estado'] ?? 'pendiente');
                        $isPending = $estado === 'pendiente';
                        $subtitle = filled($cita['fecha'] ?? null) && filled($cita['hora'] ?? null)
                            ? ($cita['fecha'] . ' · ' . $cita['hora'])
                            : ($cita['fecha'] ?? '—');
                        $departamento = (string) ($cita['departamento'] ?? '—');
                        $dayLabel = '—';
                        $dateParts = array_values(array_filter(explode('/', (string) ($cita['fecha'] ?? ''))));

                        if (!empty($dateParts[0])) {
                            $dayLabel = str_pad((string) $dateParts[0], 2, '0', STR_PAD_LEFT);
                        }

                        $monthLabel = strtoupper((string) ($cita['mes'] ?? ''));
                        $departmentColor = $resolveDepartmentColor($cita['departamento_color'] ?? null)
                            ?? $departmentFallbackColor($departamento);
                        $departmentBadgeStyle = 'border-color: '.$departmentColor.'29'.'; color: white/10; background: color-mix(in srgb, '.$departmentColor.' 14%, transparent);';
                        $rowAccentStyle = 'border-left-color: '.$departmentColor.';';
                    @endphp
                    @if ($isPending)
                        <button
                            type="button"
                            class="relative z-10 block w-full cursor-pointer text-left pointer-events-auto"
                            x-on:pointerup.stop.prevent="
                            citaInfoItem = {
                                id: {{ (int) ($cita['id'] ?? 0) }},
                                titulo: @js($cita['titulo'] ?? 'Cita'),
                                estado: @js($estado),
                                estado_label: @js($estadoLabel($estado)),
                                fecha: @js((string) ($cita['fecha'] ?? '—')),
                                hora: @js((string) ($cita['hora'] ?? '—')),
                                notes: @js((string) ($cita['lugar'] ?? '—')),
                                departamento: @js($departamento),
                                departamento_color: @js($departmentColor),
                                edit_url: @js($citaUrl),
                            };
                            showCitaInfo = true;
                        "
                        >
                            <div
                                class="tl-glass-dynamic tl-cursor-react tl-s2 tl-interactive mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                style="{{ $rowAccentStyle }}">
                                <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                                    <div
                                        class="w-[58px] shrink-0 rounded-xl border border-white/10 bg-white/5 p-2 text-center">
                                        <x-heroicon-o-calendar-days class="mx-auto h-3.5 w-3.5 text-amber-300"/>
                                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/65">{{ $monthLabel }}</p>
                                        <p class="text-2xl font-semibold leading-none text-white/95">{{ $dayLabel }}</p>
                                    </div>

                                    <div
                                        class="w-[122px] shrink-0 rounded-xl border border-white/10 bg-white/5 px-2 text-center ">
                                        <div
                                            class="inline-flex w-full flex-col items-center gap-1.5 rounded-xl  py-1.5 text-blue-100">
                                            @if ($departamento !== '—')
                                                <span
                                                    class="inline-flex w-full items-center justify-center gap-1 rounded-md border px-2 py-1 text-[10px] font-semibold leading-tight whitespace-normal text-center"
                                                    style="{{ $departmentBadgeStyle }}">
                                                    <x-heroicon-o-building-office-2 class="h-3 w-3 shrink-0"/>
                                                    <span class="break-words">{{ $departamento }}</span>
                                                </span>
                                            @endif

                                            <div class="px-2 inline-flex items-center gap-1.5">
                                                <span
                                                    class="inline-flex h-5 w-5 items-center justify-start rounded-full bg-white/5 text-blue-200">
                                                    <x-heroicon-o-clock class="h-3.5 w-3.5"/>
                                                </span>
                                                <span
                                                    class="text-sm font-semibold leading-none tabular-nums">{{ $cita['hora'] ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-white/90">{{ $cita['titulo'] ?? 'Cita' }}</p>
                                    </div>
                                </div>

                                <div class="shrink-0 self-stretch">
                                    <div
                                        class="flex h-full min-h-[76px] w-11 items-center justify-center rounded-2xl ">
                                        <div class="-rotate-90 whitespace-nowrap">
                                            <x-portal.badge :color="$appointmentBadgeColor($estado)">
                                                {{ $estadoLabel($estado) }}
                                            </x-portal.badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    @else
                        <button
                            type="button"
                            class="relative z-10 block w-full cursor-pointer text-left pointer-events-auto"
                            x-on:pointerup.stop.prevent="
                            citaInfoItem = {
                                id: {{ (int) ($cita['id'] ?? 0) }},
                                titulo: @js($cita['titulo'] ?? 'Cita'),
                                estado: @js($estado),
                                estado_label: @js($estadoLabel($estado)),
                                fecha: @js((string) ($cita['fecha'] ?? '—')),
                                hora: @js((string) ($cita['hora'] ?? '—')),
                                notes: @js((string) ($cita['lugar'] ?? '—')),
                                departamento: @js($departamento),
                                departamento_color: @js($departmentColor),
                                edit_url: @js($citaUrl),
                            };
                            showCitaInfo = true;
                        "
                        >
                            <div
                                class=" tl-s2 mb-4 flex items-center justify-between gap-3 p-3 sm:p-4 border-l-2"
                                style="{{ $rowAccentStyle }}">
                                <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                                    <div
                                        class="w-[58px] shrink-0 rounded-xl border border-white/10 bg-white/5 p-2 text-center">
                                        <x-heroicon-o-calendar-days class="mx-auto h-3.5 w-3.5 text-amber-300"/>
                                        <p class="mt-1 text-[10px] font-semibold uppercase tracking-wider text-white/65">{{ $monthLabel }}</p>
                                        <p class="text-2xl font-semibold leading-none text-white/95">{{ $dayLabel }}</p>
                                    </div>

                                    <div
                                        class="w-[122px] shrink-0 rounded-xl border border-white/10 bg-white/5 p-2 text-center ">
                                        <div
                                            class="inline-flex w-full flex-col items-center gap-1.5 rounded-xl  px-2 py-1.5 text-blue-100">
                                            @if ($departamento !== '—')
                                                <span
                                                    class="inline-flex w-full items-center justify-center gap-1 rounded-md border px-2 py-1 text-[10px] font-semibold leading-tight whitespace-normal text-center"
                                                    style="{{ $departmentBadgeStyle }}">
                                                    <x-heroicon-o-building-office-2 class="h-3 w-3 shrink-0"/>
                                                    <span class="break-words">{{ $departamento }}</span>
                                                </span>
                                            @endif

                                            <div class="inline-flex items-center gap-1.5">
                                                <span
                                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-blue-500/20 text-blue-200">
                                                    <x-heroicon-o-clock class="h-3.5 w-3.5"/>
                                                </span>
                                                <span
                                                    class="text-sm font-semibold leading-none tabular-nums">{{ $cita['hora'] ?? '—' }}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="min-w-0 flex-1">
                                        <p class="truncate font-medium text-white/90">{{ $cita['titulo'] ?? 'Cita' }}</p>
                                    </div>
                                </div>

                                <div class="shrink-0 self-stretch">
                                    <div
                                        class="flex h-full min-h-[76px] w-11 items-center justify-center rounded-2xl ">
                                        <div class="-rotate-90 whitespace-nowrap">
                                            <x-portal.badge :color="$appointmentBadgeColor($estado)">
                                                {{ $estadoLabel($estado) }}
                                            </x-portal.badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </button>
                    @endif
                @endforeach
            </div>
        @endif

        <div
            x-show="showCitaInfo"
            class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm"
            x-on:keydown.escape.window="showCitaInfo = false"
            x-on:click.self="showCitaInfo = false"

            style="display: none;"

        >
            <div class="absolute inset-0 overflow-y-auto px-4 pt-20 pb-6 sm:px-6 sm:pt-24 pointer-events-none">
                <div class="mx-auto w-full max-w-3xl pointer-events-auto" x-on:click.stop>
                    <div class="sticky top-2 z-10 -mx-2 px-2 pt-1 pb-2">

                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <div
                                    class="tl-breadcrumb inline-flex items-center gap-2 rounded-full bg-white/5 px-4 py-2">
                                    <span
                                        class="text-xs font-semibold uppercase tracking-widest text-white/85">Citas</span>
                                    <span class="text-white/30">›</span>
                                    <span
                                        class="text-xs font-semibold uppercase tracking-widest text-white/85">Detalle</span>
                                </div>
                            </div>

                            <div
                                class="tl-segment-group tl-interactive tl-interactive-active flex items-center gap-1.5 shrink-0">
                                <button
                                    type="button"
                                    x-show="citaInfoItem?.estado === 'pendiente'"
                                    class="tl-s3 tl-interactive inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                    x-on:click="
                                        showCitaInfo = false;
                                        $wire.mountAction('editCita', { record: Number(citaInfoItem?.id ?? 0) });
                                    "
                                    aria-label="Editar cita"
                                >
                                    <x-heroicon-o-pencil-square class="h-4 w-4"/>
                                </button>

                                <button
                                    type="button"
                                    class="tl-s3  tl-interactive inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                    x-show="citaInfoItem?.estado === 'confirmada'"
                                    x-on:click="
                                    citaStatusActionItem = {
                                        id: citaInfoItem?.id ?? null,
                                        titulo: citaInfoItem?.titulo ?? 'Cita',
                                        subtitle: `${citaInfoItem?.fecha ?? '—'} · ${citaInfoItem?.hora ?? '—'}`,
                                    };
                                    citaAskCancelReason = true;
                                    citaCancelMotivo = '';
                                    showCitaStatusActions = true;
                                "
                                    aria-label="Cancelar cita"
                                >
                                    <x-heroicon-o-x-mark class="h-4 w-4 text-red-200"/>
                                </button>

                                <button
                                    type="button"
                                    class="tl-s3   inline-flex h-9 w-9 items-center justify-center rounded-full border border-white/10 bg-white/5 text-white/80"
                                    x-on:pointerup="showCitaInfo = false"
                                    aria-label="Cerrar"
                                >
                                    <x-heroicon-o-chevron-left class="h-4 w-4"/>
                                </button>
                            </div>
                        </div>
                    </div>

                    <x-portal.card padding="p-6">
                        <div class="flex flex-col gap-4">
                            <div class="min-w-0">
                                <p class="text-xl font-semibold text-white/90 truncate"
                                   x-text="citaInfoItem?.titulo ?? 'Cita'"></p>
                                <p class="mt-1 text-sm text-white/45"
                                   x-text="`${citaInfoItem?.fecha ?? '—'} · ${citaInfoItem?.hora ?? '—'}`"></p>
                            </div>

                            <div class="tl-inner-surface rounded-3xl p-5 sm:p-6">
                                <p class="text-white/70 text-sm font-semibold">Resumen de la cita</p>

                                <dl class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                                    <div>
                                        <dt class="text-white/40 text-[11px] uppercase tracking-widest">Departamento
                                        </dt>
                                        <dd class="mt-2">
                                        <span
                                            class="inline-flex items-center rounded-full border px-3 py-1 text-[12px] font-medium"
                                            x-bind:style="`--dept-color: ${citaInfoItem?.departamento_color ?? '#71717a'}; border-color: var(--dept-color); color: var(--dept-color); background: color-mix(in srgb, var(--dept-color) 18%, transparent);`"
                                            x-text="citaInfoItem?.departamento ?? '—'"
                                        ></span>
                                        </dd>
                                    </div>

                                    <div>
                                        <dt class="text-white/40 text-[11px] uppercase tracking-widest">Estado</dt>
                                        <dd class="mt-1 text-white/85 font-semibold"
                                            x-text="citaInfoItem?.estado_label ?? '—'"></dd>
                                    </div>

                                    <div>
                                        <dt class="text-white/40 text-[11px] uppercase tracking-widest">Fecha</dt>
                                        <dd class="mt-1 text-blue-200 font-semibold"
                                            x-text="citaInfoItem?.fecha ?? '—'"></dd>
                                    </div>

                                    <div>
                                        <dt class="text-white/40 text-[11px] uppercase tracking-widest">Hora</dt>
                                        <dd class="mt-1 text-emerald-200 font-semibold"
                                            x-text="citaInfoItem?.hora ?? '—'"></dd>
                                    </div>

                                    <div class="sm:col-span-2">
                                        <dt class="text-white/40 text-[11px] uppercase tracking-widest">Notas</dt>
                                        <dd class="mt-1 text-white/70 text-sm whitespace-pre-line break-words"
                                            x-text="citaInfoItem?.notes ?? '—'"></dd>
                                    </div>
                                </dl>
                            </div>
                        </div>
                    </x-portal.card>
                </div>
            </div>
        </div>

        <div
            x-show="showCitaStatusActions"
            class="fixed inset-0 z-50 bg-black/70 backdrop-blur-sm"
            x-on:keydown.escape.window="showCitaStatusActions = false"
            x-on:click.self="showCitaStatusActions = false"
            style="display: none;"
        >
            <div class="absolute inset-0 overflow-y-auto px-4 py-6 sm:px-6 pointer-events-none">
                <div class="mx-auto w-full max-w-xl pointer-events-auto" x-on:click.stop>
                    <x-portal.card padding="p-6">
                        <div class="space-y-4">
                            <div>
                                <p class="text-lg font-semibold text-white/90" x-text="citaStatusActionItem?.titulo ?? 'Cancelar cita'"></p>
                                <p class="mt-1 text-sm text-white/50" x-text="citaStatusActionItem?.subtitle ?? ''"></p>
                            </div>

                            <div x-show="citaAskCancelReason" class="space-y-2">
                                <label class="text-xs uppercase tracking-widest text-white/50">Motivo de cancelación</label>
                                <textarea
                                    x-model="citaCancelMotivo"
                                    class="w-full rounded-xl border border-white/10 bg-white/5 px-3 py-2 text-sm text-white/85 placeholder:text-white/30"
                                    rows="4"
                                    placeholder="Indica el motivo"
                                ></textarea>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <x-portal.button variant="ghost" type="button" x-on:click="showCitaStatusActions = false">
                                    Cerrar
                                </x-portal.button>

                                <x-portal.button
                                    variant="danger"
                                    type="button"
                                    x-on:click="
                                        if (!citaStatusActionItem?.id) {
                                            return;
                                        }

                                        $wire.markCitaCancelada(citaStatusActionItem.id, citaCancelMotivo);
                                        showCitaStatusActions = false;
                                        showCitaInfo = false;
                                    "
                                >
                                    Confirmar cancelación
                                </x-portal.button>
                            </div>
                        </div>
                    </x-portal.card>
                </div>
            </div>
        </div>


    </div>
</div>
