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
        <x-employee-nav-fab current="attendance-roster" />
        <x-employee-help-popup page="attendance-roster" />
        <button
            type="button"
            x-on:click="toggleFs()"
            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-gray-300 bg-white text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-400 dark:hover:bg-white/10"
            :title="fs ? 'Salir de pantalla completa' : 'Pantalla completa'"
        >
            <template x-if="!fs"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15" /></svg></template>
            <template x-if="fs"><svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-4 w-4"><path stroke-linecap="round" stroke-linejoin="round" d="M9 9V4.5M9 9H4.5M9 9 3.75 3.75M9 15v4.5M9 15H4.5M9 15l-5.25 5.25M15 9h4.5M15 9V4.5M15 9l5.25-5.25M15 15h4.5M15 15v4.5m0-4.5 5.25 5.25" /></svg></template>
        </button>
    </div>

    {{-- Month navigation + department filter --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-center gap-3">
            <button wire:click="previousMonth" type="button"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
            </button>

            <h2 class="min-w-[200px] text-center text-lg font-bold text-gray-900 dark:text-white">
                {{ $monthNames[$month] }} {{ $year }}
            </h2>

            <button wire:click="nextMonth" type="button"
                class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-white p-2 text-gray-500 shadow-sm transition hover:bg-gray-50 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
            </button>
        </div>

        {{-- Legend --}}
        <div class="flex flex-wrap items-center gap-3 text-xs">
            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-100 px-2.5 py-1 font-medium text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300">
                <span class="h-2 w-2 rounded-full bg-emerald-500"></span> Presente
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 font-medium text-red-700 dark:bg-red-500/20 dark:text-red-300">
                <span class="h-2 w-2 rounded-full bg-red-500"></span> Ausente
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-100 px-2.5 py-1 font-medium text-amber-700 dark:bg-amber-500/20 dark:text-amber-300">
                <span class="h-2 w-2 rounded-full bg-amber-500"></span> Tardanza
            </span>
            <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-100 px-2.5 py-1 font-medium text-blue-700 dark:bg-blue-500/20 dark:text-blue-300">
                <span class="h-2 w-2 rounded-full bg-blue-500"></span> Permiso
            </span>
        </div>

        {{-- Department filter --}}
        <div class="w-full sm:w-56">
            <select wire:model.live="departmentId"
                class="fi-select-input block w-full rounded-lg border-gray-300 text-sm shadow-sm transition focus:border-primary-500 focus:ring-1 focus:ring-primary-500 dark:border-white/10 dark:bg-white/5 dark:text-white">
                <option value="">Todos los departamentos</option>
                @foreach($this->departments as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    {{-- Roster Grid --}}
    <div class="mt-4 overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-white/[0.02]" wire:loading.class="opacity-50">
        <table class="w-full text-xs">
            <thead>
                {{-- Day numbers --}}
                <tr class="border-b-2 border-gray-200 dark:border-white/10">
                    <th class="sticky left-0 z-20 min-w-[180px] border-r border-gray-200 bg-gray-50 px-3 py-2.5 text-left text-xs font-semibold text-gray-600 dark:border-white/10 dark:bg-gray-800/80 dark:text-gray-200">
                        Empleado
                    </th>
                    @foreach($days as $dayInfo)
                        <th class="min-w-[52px] border-l border-gray-100 px-1 py-2 text-center font-bold dark:border-white/5
                            {{ $dayInfo['isToday']
                                ? 'bg-primary-50 text-primary-700 dark:bg-primary-500/15 dark:text-primary-300'
                                : ($dayInfo['isWeekend']
                                    ? 'bg-red-50/50 text-gray-500 dark:bg-red-500/5 dark:text-gray-400'
                                    : 'bg-gray-50 text-gray-700 dark:bg-gray-800/50 dark:text-gray-200') }}">
                            <div class="text-[10px] uppercase {{ $dayInfo['isWeekend'] ? 'text-red-500 dark:text-red-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $dayInfo['dowName'] }}</div>
                            <div class="{{ $dayInfo['isToday'] ? 'flex h-6 w-6 items-center justify-center rounded-full bg-primary-600 text-white mx-auto text-xs' : 'text-sm' }}">
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
                            <button type="button" wire:click="showEmployeeInfo({{ $emp['id'] }})" class="flex w-full items-center gap-2.5 text-left transition hover:opacity-75" title="Ver ficha del empleado">
                                <div class="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-[10px] font-bold text-gray-600 dark:bg-white/10 dark:text-gray-200">
                                    @if($emp['avatar'])
                                        <img src="{{ $emp['avatar'] }}" class="h-8 w-8 rounded-full object-cover" alt="">
                                    @else
                                        {{ $emp['initials'] }}
                                    @endif
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-xs font-semibold text-gray-900 dark:text-white">{{ $emp['name'] }}</div>
                                    @if($emp['code'])
                                        <div class="text-[10px] text-gray-400 dark:text-gray-500">{{ $emp['code'] }}</div>
                                    @endif
                                </div>
                            </button>
                        </td>

                        {{-- Day cells --}}
                        @foreach($days as $i => $dayInfo)
                            @php
                                $cell = $grid[$emp['id']][$i] ?? ['hasRecord' => false];
                            @endphp
                            <td class="border-l border-gray-100 px-0.5 py-1 text-center dark:border-white/5
                                {{ $dayInfo['isToday']
                                    ? 'bg-primary-50/40 dark:bg-primary-500/5'
                                    : ($dayInfo['isWeekend']
                                        ? 'bg-red-50/30 dark:bg-red-500/[0.03]'
                                        : '') }}">
                                @if($cell['hasRecord'])
                                    @php
                                        $statusColors = match($cell['status'] ?? 'presente') {
                                            'presente' => 'bg-emerald-500',
                                            'ausente' => 'bg-red-500',
                                            'tardanza' => 'bg-amber-500',
                                            'permiso' => 'bg-blue-500',
                                            default => 'bg-gray-400',
                                        };
                                        $statusBg = match($cell['status'] ?? 'presente') {
                                            'presente' => 'bg-emerald-50 border-emerald-200 dark:bg-emerald-500/20 dark:border-emerald-400/30',
                                            'ausente' => 'bg-red-50 border-red-200 dark:bg-red-500/20 dark:border-red-400/30',
                                            'tardanza' => 'bg-amber-50 border-amber-200 dark:bg-amber-500/20 dark:border-amber-400/30',
                                            'permiso' => 'bg-blue-50 border-blue-200 dark:bg-blue-500/20 dark:border-blue-400/30',
                                            default => 'bg-gray-50 border-gray-200 dark:bg-white/5 dark:border-white/10',
                                        };
                                    @endphp
                                    <button
                                        wire:click="editAttendance({{ $cell['id'] }})"
                                        class="group relative mx-auto flex w-11 flex-col items-center rounded-md border px-1 py-0.5 transition hover:scale-105 hover:shadow-md hover:ring-2 hover:ring-offset-1 hover:ring-primary-400 {{ $statusBg }}"
                                        title="Editar: {{ $cell['in'] ?? '—' }} → {{ $cell['out'] ?? '—' }}"
                                    >
                                        <span class="h-1.5 w-1.5 rounded-full {{ $statusColors }}"></span>
                                        @if($cell['in'])
                                            <span class="mt-0.5 text-[9px] font-medium leading-tight text-gray-700 dark:text-gray-200">{{ $cell['in'] }}</span>
                                        @endif
                                        @if($cell['out'])
                                            <span class="text-[9px] leading-tight text-gray-400 dark:text-gray-500">{{ $cell['out'] }}</span>
                                        @endif
                                        <span class="absolute inset-0 flex items-center justify-center rounded-md bg-primary-500/0 text-white opacity-0 transition group-hover:bg-primary-500/10 group-hover:opacity-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3 text-primary-600 dark:text-primary-400"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                                        </span>
                                    </button>
                                @else
                                    <button
                                        wire:click="openForDay({{ $emp['id'] }}, '{{ $dayInfo['date'] }}')"
                                        class="group mx-auto flex h-8 w-11 items-center justify-center rounded-md border border-dashed border-transparent text-gray-300 transition hover:border-primary-400 hover:bg-primary-50 hover:text-primary-500 dark:text-gray-700 dark:hover:border-primary-500/50 dark:hover:bg-primary-500/10 dark:hover:text-primary-400"
                                        title="Añadir registro — {{ $dayInfo['date'] }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5 opacity-0 transition group-hover:opacity-100"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                                    </button>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($days) + 1 }}" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center gap-2">
                                <svg class="h-8 w-8 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z"/></svg>
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
        <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
            @php
                $totalRecords = collect($grid)->flatten(1)->where('hasRecord', true)->count();
                $totalPresente = collect($grid)->flatten(1)->where('hasRecord', true)->where('status', 'presente')->count();
                $totalAusente = collect($grid)->flatten(1)->where('hasRecord', true)->where('status', 'ausente')->count();
                $totalTardanza = collect($grid)->flatten(1)->where('hasRecord', true)->where('status', 'tardanza')->count();
            @endphp
            <div class="rounded-xl border border-gray-200 bg-white p-4 dark:border-white/10 dark:bg-white/5">
                <div class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalRecords }}</div>
                <div class="text-xs text-gray-500 dark:text-gray-400">Total registros</div>
            </div>
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-400/30 dark:bg-emerald-500/10">
                <div class="text-2xl font-bold text-emerald-600 dark:text-emerald-300">{{ $totalPresente }}</div>
                <div class="text-xs text-emerald-600/70 dark:text-emerald-400/70">Presentes</div>
            </div>
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 dark:border-red-400/30 dark:bg-red-500/10">
                <div class="text-2xl font-bold text-red-600 dark:text-red-300">{{ $totalAusente }}</div>
                <div class="text-xs text-red-600/70 dark:text-red-400/70">Ausentes</div>
            </div>
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-400/30 dark:bg-amber-500/10">
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-300">{{ $totalTardanza }}</div>
                <div class="text-xs text-amber-600/70 dark:text-amber-400/70">Tardanzas</div>
            </div>
        </div>
    @endif
</div>
</x-filament-panels::page>
