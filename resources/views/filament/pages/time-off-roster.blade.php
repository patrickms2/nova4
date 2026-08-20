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
            <x-employee-nav-fab current="time-off-roster"/>
            <x-employee-help-popup page="time-off-roster"/>
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
                    class="inline-flex items-center gap-1 rounded-full bg-purple-100 px-2.5 py-1 font-medium text-purple-700 dark:bg-purple-500/20 dark:text-purple-300">Vacaciones</span>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-blue-100 px-2.5 py-1 font-medium text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">Permiso</span>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-teal-100 px-2.5 py-1 font-medium text-teal-700 dark:bg-teal-500/20 dark:text-teal-300">Personal</span>
                <span
                    class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 font-medium text-red-700 dark:bg-red-500/20 dark:text-red-300">Baja</span>
            </div>


        </div>

        {{-- Time-off Roster Grid --}}
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
                                    <div
                                        class="truncate text-xs font-semibold text-gray-900 dark:text-white">{{ $emp['name'] }}</div>
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
                                $cell = $grid[$emp['id']][$i] ?? ['hasTimeOff' => false];
                                $pendingSwap = $cell['pendingSwap'] ?? null;
                            @endphp
                            <td class="border-l border-gray-100 px-0.5 py-1 text-center dark:border-white/5
                                {{ $dayInfo['isToday']
                                    ? 'bg-primary-50/40 dark:bg-primary-500/5'
                                    : ($dayInfo['isWeekend']
                                        ? 'bg-red-50/30 dark:bg-red-500/[0.03]'
                                        : '') }}">
                                @if($pendingSwap)
                                    <button
                                        wire:click="reviewSwapRequest({{ $pendingSwap['id'] }})"
                                        class="group relative mx-auto flex h-8 w-10 items-center justify-center rounded-md border-2 border-red-500 bg-red-100 text-[9px] font-bold text-red-700 animate-pulse dark:bg-red-500/30 dark:text-red-200 dark:border-red-400/60"
                                        title="Intercambio pendiente con {{ $pendingSwap['partner_name'] ?? 'Compañero' }}"
                                    >
                                        ⇄
                                        <span
                                            class="absolute -top-1 -right-1 flex h-3.5 w-3.5 items-center justify-center rounded-full bg-red-600 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">!</span>
                                    </button>
                                @elseif($cell['hasTimeOff'])
                                    @php
                                        $isPending = ($cell['status'] ?? 'pending') === 'pending';
                                        $typeColors = match($cell['type'] ?? '') {
                                            'vacaciones' => 'bg-purple-100 text-purple-800 border-purple-300 dark:bg-purple-500/25 dark:text-purple-200 dark:border-purple-400/40',
                                            'permiso'    => 'bg-blue-100 text-blue-800 border-blue-300 dark:bg-blue-500/25 dark:text-blue-200 dark:border-blue-400/40',
                                            'personal'   => 'bg-teal-100 text-teal-800 border-teal-300 dark:bg-teal-500/25 dark:text-teal-200 dark:border-teal-400/40',
                                            'baja'       => 'bg-red-100 text-red-800 border-red-300 dark:bg-red-500/25 dark:text-red-200 dark:border-red-400/40',
                                            default      => 'bg-gray-100 text-gray-600 border-gray-200 dark:bg-white/5 dark:text-gray-400 dark:border-white/10',
                                        };
                                        $pendingColors = 'border-2 border-amber-400 bg-amber-100 text-amber-800 dark:bg-amber-500/30 dark:text-amber-200 dark:border-amber-400/60 animate-pulse';
                                        $typeLetter = match($cell['type'] ?? '') {
                                            'vacaciones' => 'V',
                                            'permiso'    => 'P',
                                            'personal'   => 'D',
                                            'baja'       => 'B',
                                            default      => '?',
                                        };
                                        $statusDot = match($cell['status'] ?? 'pending') {
                                            'approved' => 'bg-emerald-500',
                                            'denied'   => 'bg-red-500',
                                            default    => 'bg-amber-400',
                                        };
                                    @endphp
                                    <button
                                        wire:click="editTimeOff({{ $cell['id'] }})"
                                        class="group relative mx-auto flex h-8 w-10 items-center justify-center rounded-md text-xs font-bold transition hover:scale-110 hover:shadow-md hover:ring-2 hover:ring-offset-1 hover:ring-primary-400 {{ $isPending ? $pendingColors : 'border '.$typeColors }}"
                                        title="{{ $isPending ? '⚠ PENDIENTE — ' : 'Editar: ' }}{{ $typeLabels[$cell['type']] ?? $cell['type'] }}"
                                    >
                                        {{ $typeLetter }}
                                        @if($isPending)
                                            <span
                                                class="absolute -top-1 -right-1 flex h-3 w-3 items-center justify-center rounded-full bg-amber-500 text-[7px] font-bold text-white ring-1 ring-white dark:ring-gray-900">!</span>
                                        @else
                                            <span
                                                class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full ring-1 ring-white dark:ring-gray-900 {{ $statusDot }}"></span>
                                        @endif
                                        <span
                                            class="absolute inset-0 flex items-center justify-center rounded-md bg-primary-500/0 opacity-0 transition group-hover:bg-primary-500/10 group-hover:opacity-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"
                                                 fill="currentColor"
                                                 class="h-3 w-3 text-primary-600 dark:text-primary-400"><path
                                                    d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z"/></svg>
                                        </span>
                                    </button>
                                @else
                                    <button
                                        wire:click="openForDay({{ $emp['id'] }}, '{{ $dayInfo['date'] }}')"
                                        class="group mx-auto flex h-8 w-10 items-center justify-center rounded-md border border-dashed border-transparent text-gray-300 transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-500 dark:text-gray-700 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                                        title="Añadir ausencia — {{ $dayInfo['date'] }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                                             class="h-3.5 w-3.5 opacity-0 transition group-hover:opacity-100">
                                            <path
                                                d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z"/>
                                        </svg>
                                    </button>
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
                                          d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z"/>
                                </svg>
                                <span>No hay empleados para mostrar</span>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <x-filament-actions::modals/>
    </div>
</x-filament-panels::page>
