<x-filament-panels::page>
    @php
        $contractLabels = [
            'full_time'     => 'Completa',
            'part_time'     => 'Parcial',
            'rotating'      => 'Rotativos',
            'nights_only'   => 'Solo noche',
            'mornings_only' => 'Solo mañana',
        ];
        $prefLabels = ['M' => 'Mañana', 'T' => 'Tarde', 'N' => 'Noche', 'any' => 'Cualquiera'];
    @endphp

    {{-- Toolbar: Nav + Help --}}
    <div class="flex items-center justify-end gap-2 mb-2">
        <x-employee-nav-fab current="employee-metrics" />
        <x-employee-help-popup page="employee-metrics" />
    </div>

    {{-- Filters --}}
    <div class="mb-5 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Desde</label>
            <input type="date" wire:model.live="dateFrom" value="{{ $this->dateFrom }}"
                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Hasta</label>
            <input type="date" wire:model.live="dateTo" value="{{ $this->dateTo }}"
                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Departamento</label>
            <select wire:model.live="departmentId"
                class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-sm dark:border-white/10 dark:bg-gray-800 dark:text-gray-200">
                <option value="">Todos</option>
                @foreach($this->departments as $deptId => $deptName)
                    <option value="{{ $deptId }}" {{ (string)$this->departmentId === (string)$deptId ? 'selected' : '' }}>{{ $deptName }}</option>
                @endforeach
            </select>
        </div>
        <div class="ml-auto text-xs text-gray-400 dark:text-gray-500 self-end pb-1">
            {{ count($this->rows) }} empleados · Ordenado por fines de semana ↓
        </div>
    </div>

    {{-- Table --}}
    <div class="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm dark:border-white/10 dark:bg-gray-900">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="border-b border-gray-100 bg-gray-50 dark:border-white/10 dark:bg-white/5">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Empleado</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Departamento</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Contrato</th>
                    <th class="px-3 py-3 text-left text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Pref.</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-blue-500 dark:text-blue-400" title="Turnos de mañana">M</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-amber-500 dark:text-amber-400" title="Turnos de tarde">T</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-indigo-500 dark:text-indigo-400" title="Turnos de noche">N</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-sky-500 dark:text-sky-400" title="Total turnos trabajados">Total</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-orange-500 dark:text-orange-400" title="Turnos en fin de semana">F.Sem.</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500 dark:text-gray-400" title="Días libres (total / solicitados / auto-noche)">Libres</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-violet-500 dark:text-violet-400" title="Días de vacaciones (turno V)">Vac.</th>
                    <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-rose-500 dark:text-rose-400" title="Días de permiso/time-off aprobados">Permisos</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @forelse($this->rows as $row)
                @php
                    $weekendExcess = $row['max_weekends'] !== null && $row['weekend'] > $row['max_weekends'];
                @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-white/5 transition-colors {{ $weekendExcess ? 'bg-orange-50 dark:bg-orange-500/5' : '' }}">
                    <td class="px-4 py-2.5">
                        <a href="{{ \App\Filament\App\Resources\Employees\EmployeeResource::getUrl('calendario', ['record' => $row['id']]) }}" class="block hover:underline">
                            <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $row['name'] }}</div>
                        </a>
                        @if($row['max_weekends'] !== null)
                        <div class="text-[10px] text-gray-400 dark:text-gray-500">Máx. F.sem: {{ $row['max_weekends'] }}</div>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $row['dept'] ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $contractLabels[$row['contract_type'] ?? ''] ?? '—' }}</td>
                    <td class="px-3 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $prefLabels[$row['shift_preference'] ?? ''] ?? '—' }}</td>
                    @php $calUrl = \App\Filament\App\Resources\Employees\EmployeeResource::getUrl('calendario', ['record' => $row['id']]); @endphp
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md bg-blue-50 px-1.5 py-0.5 text-xs font-bold text-blue-700 dark:bg-blue-500/15 dark:text-blue-300 hover:ring-2 hover:ring-blue-400/50 transition" title="Ver calendario">{{ $row['morning'] }}</a>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md bg-amber-50 px-1.5 py-0.5 text-xs font-bold text-amber-700 dark:bg-amber-500/15 dark:text-amber-300 hover:ring-2 hover:ring-amber-400/50 transition" title="Ver calendario">{{ $row['afternoon'] }}</a>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md bg-indigo-50 px-1.5 py-0.5 text-xs font-bold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300 hover:ring-2 hover:ring-indigo-400/50 transition" title="Ver calendario">{{ $row['night'] }}</a>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md bg-sky-50 px-1.5 py-0.5 text-xs font-bold text-sky-700 dark:bg-sky-500/15 dark:text-sky-300 hover:ring-2 hover:ring-sky-400/50 transition" title="Ver calendario">{{ $row['total_working'] }}</a>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md px-1.5 py-0.5 text-xs font-bold {{ $weekendExcess ? 'bg-orange-100 text-orange-700 dark:bg-orange-500/20 dark:text-orange-300' : 'bg-orange-50 text-orange-600 dark:bg-orange-500/10 dark:text-orange-400' }} hover:ring-2 hover:ring-orange-400/50 transition" title="Ver calendario">
                            {{ $row['weekend'] }}{{ $weekendExcess ? ' !' : '' }}
                        </a>
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        <a href="{{ $calUrl }}" class="inline-block min-w-[28px] rounded-md bg-gray-50 px-1.5 py-0.5 text-xs font-bold text-gray-700 dark:bg-white/10 dark:text-gray-300 hover:ring-2 hover:ring-gray-400/50 transition"
                            title="Total: {{ $row['free'] }} | Solicitados: {{ $row['free_requested'] }} | Auto-noche: {{ $row['free_auto_night'] }}">
                            {{ $row['free'] }}
                        </a>
                        @if ($row['free_requested'] > 0 || $row['free_auto_night'] > 0)
                            <div class="text-[9px] text-gray-400">
                                {{ $row['free_requested'] > 0 ? $row['free_requested'].'s' : '' }}{{ $row['free_auto_night'] > 0 ? ' ·'.$row['free_auto_night'].'n' : '' }}
                            </div>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        @if($row['vacation'] > 0)
                            <a href="{{ \App\Filament\App\Resources\Employees\EmployeeResource::getUrl('vacaciones', ['record' => $row['id']]) }}" class="inline-block min-w-[28px] rounded-md bg-violet-50 px-1.5 py-0.5 text-xs font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300 hover:ring-2 hover:ring-violet-400/50 transition" title="Ver vacaciones">{{ $row['vacation'] }}</a>
                        @else
                            <span class="inline-block min-w-[28px] rounded-md bg-violet-50 px-1.5 py-0.5 text-xs font-bold text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">{{ $row['vacation'] }}</span>
                        @endif
                    </td>
                    <td class="px-3 py-2.5 text-center">
                        @if($row['timeoff_days'] > 0)
                            <a href="{{ \App\Filament\App\Resources\Employees\EmployeeResource::getUrl('vacaciones', ['record' => $row['id']]) }}" class="inline-block min-w-[28px] rounded-md bg-rose-50 px-1.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300 hover:ring-2 hover:ring-rose-400/50 transition" title="Ver permisos">{{ $row['timeoff_days'] }}</a>
                        @else
                            <span class="inline-block min-w-[28px] rounded-md bg-rose-50 px-1.5 py-0.5 text-xs font-bold text-rose-700 dark:bg-rose-500/15 dark:text-rose-300">{{ $row['timeoff_days'] }}</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-4 py-8 text-center text-sm text-gray-400 dark:text-gray-500">Sin empleados para el período seleccionado.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Legend --}}
    <div class="mt-3 flex flex-wrap gap-4 text-[11px] text-gray-400 dark:text-gray-500">
        <span><strong class="text-blue-500">M</strong> = Mañana</span>
        <span><strong class="text-amber-500">T</strong> = Tarde (T/P)</span>
        <span><strong class="text-indigo-500">N</strong> = Noche</span>
        <span><strong class="text-orange-500">F.Sem.</strong> = Turnos trabajados en Sáb/Dom · <span class="text-orange-400">!</span> = supera máximo configurado</span>
        <span>Libres: s = solicitados, n = post-noche</span>
    </div>
</x-filament-panels::page>
