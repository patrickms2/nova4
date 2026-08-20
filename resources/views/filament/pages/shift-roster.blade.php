<x-filament-panels::page>
    <div
        x-data="{
        fs: false,
        init() {
            document.addEventListener('fullscreenchange', () => { this.fs = !!document.fullscreenElement; });
        },
        toggleFs() {
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen();
            } else {
                document.exitFullscreen();
            }
        }
    }"
    >
        {{-- Toolbar: Nav + Help + Fullscreen --}}
        <div class="flex items-center justify-end gap-2 mb-2">
            <x-employee-nav-fab current="shift-roster"/>
            <x-employee-help-popup page="shift-roster"/>
            <button
                type="button"
                x-on:click="toggleFs()"
                class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10"
                :title="fs ? 'Salir de pantalla completa' : 'Pantalla completa'"
            >
                <template x-if="!fs">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"/>
                    </svg>
                </template>
                <template x-if="fs">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25"/>
                    </svg>
                </template>
            </button>
        </div>

        {{-- Month navigation + department filter --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <button wire:click="previousMonth" type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5"/>
                    </svg>
                </button>

                <h2 class="min-w-[200px] text-center text-lg font-bold text-gray-900 dark:text-white">
                    {{ $monthNames[$month] }} {{ $year }}
                </h2>

                <button wire:click="nextMonth" type="button"
                        class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                         stroke="currentColor" class="h-5 w-5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/>
                    </svg>
                </button>
            </div>

            {{-- Legend --}}
            <div class="flex flex-wrap items-center gap-2 text-xs">
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-sky-100 px-2.5 py-1 font-bold text-sky-700 dark:bg-sky-500/20 dark:text-sky-300">M</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 font-bold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">P</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 font-bold text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">N</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 font-bold text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">L</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-purple-100 px-2.5 py-1 font-bold text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">V</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 font-bold text-red-700 dark:bg-red-500/20 dark:text-red-300">B</span>
                <span
                    class="inline-flex items-center gap-1.5 rounded-full bg-gray-200 px-2.5 py-1 font-bold text-gray-600 dark:bg-white/10 dark:text-gray-300">S</span>
            </div>

            {{-- Department filter --}}
            <div class="w-full sm:w-56">
                <select wire:model.live="departmentId"
                        class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                    <option value="">Todos los departamentos</option>
                    @foreach($this->departments as $id => $name)
                        <option @if($id == $departmentId) selected @endif value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Shift Roster Grid --}}
        <div
            class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.02]"
            wire:loading.class="opacity-50">
            <table class="w-full text-xs">
                <thead>
                <tr class="border-b-2 border-gray-200 dark:border-white/10">
                    <th class="sticky left-0 z-20 min-w-[200px] border-r border-gray-200 bg-gray-50 px-3 py-2.5 text-left text-xs font-semibold text-gray-600 dark:border-white/10 dark:bg-gray-800/80 dark:text-gray-200">
                        Empleado
                    </th>
                    @foreach($days as $dayInfo)
                        <th class="min-w-[44px] border-l border-gray-100 px-0.5 py-2 text-center font-bold dark:border-white/5
                            {{ $dayInfo['isToday']
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300'
                                : ($dayInfo['isWeekend']
                                    ? 'bg-red-50/50 text-gray-500 dark:bg-red-500/5 dark:text-gray-400'
                                    : 'bg-gray-50 text-gray-700 dark:bg-gray-800/50 dark:text-gray-200') }}">
                            <div
                                class="text-[10px] uppercase {{ $dayInfo['isWeekend'] ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $dayInfo['dowName'] }}</div>
                            <div
                                class="{{ $dayInfo['isToday'] ? 'flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-white mx-auto text-xs' : 'text-sm' }}">
                                {{ $dayInfo['day'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-white/5">
                @forelse($employees as $emp)
                    <tr class="transition hover:bg-gray-50/80 dark:hover:bg-white/[0.03]">
                        {{-- Employee info --}}
                        <td class="sticky left-0 z-10 border-r border-gray-200 bg-white px-3 py-2 dark:border-white/10 dark:bg-gray-900">
                            <button type="button" wire:click="showEmployeeInfo({{ $emp['id'] }})"
                                    class="flex w-full items-center gap-2.5 text-left transition hover:opacity-75"
                                    title="Ver ficha del empleado">
                                <div
                                    class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-600 dark:bg-white/10 dark:text-gray-200">
                                    @if($emp['avatar'])
                                        <img src="{{ $emp['avatar'] }}" class="h-8 w-8 rounded-full object-cover"
                                             alt="">
                                    @else
                                        {{ $emp['initials'] }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            class="truncate text-xs font-semibold text-gray-900 dark:text-white">{{ $emp['name'] }}</span>
                                        @if(($pendingCounts[$emp['id']] ?? 0) > 0)
                                            <span
                                                class="shrink-0 inline-flex items-center gap-0.5 rounded bg-amber-100 px-1 py-px text-[8px] font-bold text-amber-700 dark:bg-amber-500/20 dark:text-amber-300 animate-pulse"
                                                title="{{ $pendingCounts[$emp['id']] }} solicitud(es) pendiente(s)">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                     stroke-width="2" stroke="currentColor" class="h-2.5 w-2.5"><path
                                                        stroke-linecap="round" stroke-linejoin="round"
                                                        d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z"/></svg>
                                                {{ $pendingCounts[$emp['id']] }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        @if($emp['code'])
                                            <span
                                                class="text-[10px] text-gray-400 dark:text-gray-500">{{ $emp['code'] }}</span>
                                        @endif
                                        @if($emp['dept'])
                                            <span
                                                class="text-[10px] text-gray-400 dark:text-gray-500">· {{ $emp['dept'] }}</span>
                                        @endif
                                    </div>
                                </div>
                            </button>
                        </td>

                        {{-- Day cells --}}
                        @foreach($days as $i => $dayInfo)
                            @php
                                $cell = $grid[$emp['id']][$i] ?? ['hasShift' => false];
                                $hasPending = $cell['timeoffPending'] ?? false;
                                $hasApprovedTimeoff = $cell['timeoffApproved'] ?? false;
                                $timeoffType = $cell['timeoffType'] ?? '';
                                $pendingSwap = $cell['pendingSwap'] ?? null;
                                $cellBg = '';
                                if ($pendingSwap) {
                                    $cellBg = 'bg-red-50 dark:bg-red-500/10';
                                } elseif ($hasPending) {
                                    $cellBg = 'bg-amber-50 dark:bg-amber-500/10';
                                } elseif ($hasApprovedTimeoff && ($cell['hasShift'] && !in_array($cell['code'] ?? '', ['L', 'V', 'B']))) {
                                    $cellBg = 'bg-red-50 dark:bg-red-500/10';
                                } elseif ($dayInfo['isToday']) {
                                    $cellBg = 'bg-primary-50/40 dark:bg-primary-500/5';
                                } elseif ($dayInfo['isWeekend']) {
                                    $cellBg = 'bg-red-50/30 dark:bg-red-500/[0.03]';
                                }
                            @endphp
                            <td class="border-l border-gray-100 px-0.5 py-1 text-center dark:border-white/5 {{ $cellBg }}">
                                @if($pendingSwap)
                                    @php
                                        $code = $cell['code'] ?? 'L';
                                        $partner = $pendingSwap['partner_name'] ?? 'Compañero';
                                    @endphp
                                    <button
                                        wire:click="reviewSwapRequest({{ $pendingSwap['id'] }})"
                                        class="group relative mx-auto flex h-8 w-10 flex-col items-center justify-center rounded-md border-2 border-red-500 text-xs font-bold animate-pulse bg-red-100 text-red-700 dark:bg-red-500/30 dark:text-red-200 dark:border-red-400/60"
                                        title="Intercambio pendiente con {{ $partner }}"
                                    >
                                        {{ $code }}
                                        <span
                                            class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-600 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">⇄</span>
                                    </button>
                                @elseif($hasPending)
                                    {{-- PENDING time-off: show pulsing amber overlay on the shift --}}
                                    @if($cell['hasShift'])
                                        @php
                                            $code = $cell['code'] ?? 'L';
                                        @endphp
                                        <button
                                            wire:click="editShift({{ $cell['id'] }})"
                                            class="group relative mx-auto flex h-8 w-10 flex-col items-center justify-center rounded-md border-2 border-amber-400 text-xs font-bold animate-pulse bg-amber-100 text-amber-800 dark:bg-amber-500/30 dark:text-amber-200 dark:border-amber-400/60"
                                            title="⚠ SOLICITUD PENDIENTE ({{ $timeoffType }}) — Turno actual: {{ $shiftLabels[$code] ?? $code }}"
                                        >
                                            {{ $code }}
                                            <span
                                                class="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full bg-amber-500 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">!</span>
                                        </button>
                                    @else
                                        <div
                                            class="group relative mx-auto flex h-8 w-10 items-center justify-center rounded-md border-2 border-dashed border-amber-400 animate-pulse bg-amber-50 dark:bg-amber-500/20"
                                            title="⚠ SOLICITUD PENDIENTE ({{ $timeoffType }})">
                                            <span
                                                class="text-[9px] font-bold text-amber-600 dark:text-amber-300">PND</span>
                                            <span
                                                class="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full bg-amber-500 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">!</span>
                                        </div>
                                    @endif
                                @elseif($hasApprovedTimeoff && $cell['hasShift'] && !in_array($cell['code'] ?? '', ['L', 'V', 'B']))
                                    {{-- APPROVED time-off but shift still assigned: SUPER DANGER — needs swap/coverage --}}
                                    @php $code = $cell['code'] ?? 'L'; @endphp
                                    <button
                                        wire:click="openCoverageSwap({{ $emp['id'] }}, '{{ $dayInfo['date'] }}')"
                                        class="group relative mx-auto flex h-8 w-10 flex-col items-center justify-center rounded-md border-2 border-red-500 text-xs font-bold animate-pulse bg-red-100 text-red-700 dark:bg-red-500/30 dark:text-red-200 dark:border-red-400/60"
                                        title="🚨 VACÍO — Día libre aprobado ({{ $timeoffType }}) pero turno {{ $shiftLabels[$code] ?? $code }} sin cubrir. ¡Necesita swap!"
                                    >
                                        <span class="line-through opacity-50">{{ $code }}</span>
                                        <span
                                            class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-600 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">⚠</span>
                                    </button>
                                @elseif($cell['hasShift'])
                                    @php
                                        $code = $cell['code'] ?? 'L';
                                        $shiftColors = match($code) {
                                            'M' => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-500/25 dark:text-sky-200 dark:border-sky-400/40',
                                            'P' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-500/25 dark:text-amber-200 dark:border-amber-400/40',
                                            'N' => 'bg-indigo-100 text-indigo-800 border-indigo-300 dark:bg-indigo-500/25 dark:text-indigo-200 dark:border-indigo-400/40',
                                            'L' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-500/25 dark:text-emerald-200 dark:border-emerald-400/40',
                                            'V' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-500/25 dark:text-purple-200 dark:border-purple-400/40',
                                            'B' => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-500/25 dark:text-red-200 dark:border-red-400/40',
                                            'S' => 'bg-gray-200 text-gray-700 border-gray-300 dark:bg-white/10 dark:text-gray-300 dark:border-white/20',
                                            default => 'bg-gray-100 text-gray-500 border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10',
                                        };
                                        $statusDot = match($cell['status'] ?? 'planned') {
                                            'confirmed' => 'bg-emerald-500',
                                            'locked' => 'bg-red-500',
                                            default => 'bg-amber-400',
                                        };
                                    @endphp
                                    <button
                                        wire:click="editShift({{ $cell['id'] }})"
                                        class="group relative mx-auto flex h-8 w-10 flex-col items-center justify-center rounded-md border text-xs font-bold transition hover:scale-110 hover:shadow-md hover:ring-2 hover:ring-offset-1 hover:ring-primary-400 {{ $shiftColors }}"
                                        title="Editar: {{ $shiftLabels[$code] ?? $code }} — {{ ucfirst($cell['status'] ?? 'planned') }}"
                                    >
                                        {{ $code }}
                                        <span
                                            class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full ring-1 ring-white dark:ring-gray-900 {{ $statusDot }}"></span>
                                        <span
                                            class="absolute inset-0 flex items-center justify-center rounded-md bg-primary-500/0 opacity-0 transition group-hover:bg-primary-500/10 group-hover:opacity-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                 fill="currentColor"
                                                 class="h-3 w-3 text-primary-600 dark:text-primary-400"><path
                                                    d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"/></svg>
                                        </span>
                                    </button>
                                @else
                                    @if($hasApprovedTimeoff)
                                        {{-- Approved time-off, no shift (libre/vacaciones already set) --}}
                                        <div
                                            class="mx-auto flex h-8 w-10 items-center justify-center rounded-md border border-purple-300 bg-purple-50 text-[9px] font-bold text-purple-600 dark:bg-purple-500/20 dark:text-purple-300 dark:border-purple-400/40"
                                            title="Día libre aprobado ({{ $timeoffType }})">
                                            {{ match($timeoffType) { 'vacaciones' => 'V', 'baja' => 'B', default => 'D' } }}
                                        </div>
                                    @else
                                        <button
                                            wire:click="openForDay({{ $emp['id'] }}, '{{ $dayInfo['date'] }}')"
                                            class="group mx-auto flex h-8 w-10 items-center justify-center rounded-md border border-dashed border-transparent text-gray-300 transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-500 dark:text-gray-700 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                                            title="Añadir turno — {{ $dayInfo['date'] }}"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                 fill="currentColor"
                                                 class="h-3.5 w-3.5 opacity-0 transition group-hover:opacity-100">
                                                <path
                                                    d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/>
                                            </svg>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}"
                            class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                     stroke-width="1.5" stroke="currentColor"
                                     class="h-8 w-8 text-gray-300 dark:text-gray-600">
                                    <path stroke-linecap="round" stroke-linejoin="round"
                                          d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5"/>
                                </svg>
                                <span>No hay empleados para mostrar</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        {{-- Summary stats --}}
        @if(count($employees) > 0)
            @php
                $allCells = collect($grid)->flatMap(fn($row) => $row)->where('hasShift', true);
                $totalShifts = $allCells->count();
                $countByCode = $allCells->groupBy('code')->map->count();
            @endphp
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-8">
                <div class="rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5">
                    <div class="text-xl font-bold text-gray-900 dark:text-white">{{ $totalShifts }}</div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">Total turnos</div>
                </div>
                <div class="rounded-xl border border-sky-200 bg-sky-50 p-3 dark:border-sky-400/30 dark:bg-sky-500/10">
                    <div class="text-xl font-bold text-sky-600 dark:text-sky-300">{{ $countByCode->get('M', 0) }}</div>
                    <div class="text-[10px] text-sky-600/70 dark:text-sky-400/70">Mañana</div>
                </div>
                <div
                    class="rounded-xl border border-amber-200 bg-amber-50 p-3 dark:border-amber-400/30 dark:bg-amber-500/10">
                    <div
                        class="text-xl font-bold text-amber-600 dark:text-amber-300">{{ $countByCode->get('P', 0) }}</div>
                    <div class="text-[10px] text-amber-600/70 dark:text-amber-400/70">Partido</div>
                </div>
                <div
                    class="rounded-xl border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-400/30 dark:bg-indigo-500/10">
                    <div
                        class="text-xl font-bold text-indigo-600 dark:text-indigo-300">{{ $countByCode->get('N', 0) }}</div>
                    <div class="text-[10px] text-indigo-600/70 dark:text-indigo-400/70">Noche</div>
                </div>
                <div
                    class="rounded-xl border border-emerald-200 bg-emerald-50 p-3 dark:border-emerald-400/30 dark:bg-emerald-500/10">
                    <div
                        class="text-xl font-bold text-emerald-600 dark:text-emerald-300">{{ $countByCode->get('L', 0) }}</div>
                    <div class="text-[10px] text-emerald-600/70 dark:text-emerald-400/70">Libre</div>
                </div>
                <div
                    class="rounded-xl border border-purple-200 bg-purple-50 p-3 dark:border-purple-400/30 dark:bg-purple-500/10">
                    <div
                        class="text-xl font-bold text-purple-600 dark:text-purple-300">{{ $countByCode->get('V', 0) }}</div>
                    <div class="text-[10px] text-purple-600/70 dark:text-purple-400/70">Vacaciones</div>
                </div>
                <div class="rounded-xl border border-red-200 bg-red-50 p-3 dark:border-red-400/30 dark:bg-red-500/10">
                    <div class="text-xl font-bold text-red-600 dark:text-red-300">{{ $countByCode->get('B', 0) }}</div>
                    <div class="text-[10px] text-red-600/70 dark:text-red-400/70">Baja</div>
                </div>
                <div class="rounded-xl border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
                    <div
                        class="text-xl font-bold text-gray-600 dark:text-gray-300">{{ $countByCode->get('S', 0) }}</div>
                    <div class="text-[10px] text-gray-500 dark:text-gray-400">Saliente</div>
                </div>
            </div>
        @endif

        <x-filament-actions::modals/>
    </div>
</x-filament-panels::page>
