@php
    use App\Models\EmployeeShift;
    use App\Models\EmployeeTimeOff;

    $shiftColors = [
        'M' => 'bg-sky-100 text-sky-800 dark:bg-sky-500/25 dark:text-sky-200',
        'P' => 'bg-amber-100 text-amber-800 dark:bg-amber-500/25 dark:text-amber-200',
        'N' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-500/25 dark:text-indigo-200',
        'L' => 'bg-emerald-100 text-emerald-800 dark:bg-emerald-500/25 dark:text-emerald-200',
        'V' => 'bg-purple-100 text-purple-800 dark:bg-purple-500/25 dark:text-purple-200',
        'B' => 'bg-red-100 text-red-800 dark:bg-red-500/25 dark:text-red-200',
        'S' => 'bg-gray-200 text-gray-700 dark:bg-white/10 dark:text-gray-300',
    ];
    $shiftLabels = ['M' => 'Mañana', 'P' => 'Partido', 'N' => 'Noche', 'L' => 'Libre', 'V' => 'Vacaciones', 'B' => 'Baja', 'S' => 'Saliente'];
    $statusColors = [
        EmployeeTimeOff::STATUS_PENDING  => 'bg-amber-100 text-amber-700 dark:bg-amber-500/20 dark:text-amber-300',
        EmployeeTimeOff::STATUS_APPROVED => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300',
        EmployeeTimeOff::STATUS_DENIED   => 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300',
    ];
@endphp

<div class="space-y-5">
    {{-- Header --}}
    <div class="flex items-center gap-4">
        <div class="flex h-16 w-16 flex-shrink-0 items-center justify-center rounded-full bg-gray-200 text-xl font-bold text-gray-600 dark:bg-white/10 dark:text-gray-200">
            @if($emp?->avatar_url)
                <img src="{{ $emp->avatar_url }}" class="h-16 w-16 rounded-full object-cover" alt="">
            @else
                {{ strtoupper(mb_substr($emp?->name ?? '?', 0, 1)) }}{{ strtoupper(mb_substr(mb_strstr($emp?->name ?? '', ' ') ?: '', 1, 1)) }}
            @endif
        </div>
        <div>
            <div class="text-lg font-bold text-gray-900 dark:text-white">{{ $emp?->name }}</div>
            @if($emp?->employee_code)
                <div class="text-sm text-gray-500 dark:text-gray-400">{{ $emp->employee_code }}</div>
            @endif
            @if($emp?->bookingDepartment)
                <span class="mt-1 inline-flex items-center rounded-full bg-primary-100 px-2.5 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-500/20 dark:text-primary-300">
                    {{ $emp->bookingDepartment->name }}
                </span>
            @endif
        </div>
    </div>

    {{-- Info grid --}}
    <div class="grid grid-cols-2 gap-3 text-sm">
        @if($emp?->email)
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500">Email</div>
            <div class="mt-0.5 truncate text-gray-700 dark:text-gray-200">{{ $emp->email }}</div>
        </div>
        @endif
        @if($emp?->phone)
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500">Teléfono</div>
            <div class="mt-0.5 text-gray-700 dark:text-gray-200">{{ $emp->phone }}</div>
        </div>
        @endif
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500">Permisos pendientes</div>
            <div class="mt-0.5 font-semibold {{ $pendingTimeOff > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-700 dark:text-gray-200' }}">{{ $pendingTimeOff }}</div>
        </div>
        <div class="rounded-lg bg-gray-50 p-3 dark:bg-white/5">
            <div class="text-xs font-medium text-gray-400 dark:text-gray-500">Estado</div>
            <div class="mt-0.5">
                <span class="inline-flex items-center rounded-full {{ $emp?->status ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/20 dark:text-emerald-300' : 'bg-red-100 text-red-700 dark:bg-red-500/20 dark:text-red-300' }} px-2 py-0.5 text-xs font-medium">
                    {{ $emp?->status ? 'Activo' : 'Inactivo' }}
                </span>
            </div>
        </div>
    </div>

    {{-- Recent shifts --}}
    @if($recentShifts->isNotEmpty())
    <div>
        <div class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Últimos turnos</div>
        <div class="flex flex-wrap gap-2">
            @foreach($recentShifts as $shift)
                <div class="flex items-center gap-1.5 rounded-lg border border-gray-100 px-2.5 py-1.5 dark:border-white/10">
                    <span class="inline-flex h-6 w-6 items-center justify-center rounded-md text-xs font-bold {{ $shiftColors[$shift->shift_code] ?? 'bg-gray-100 text-gray-600' }}">
                        {{ $shift->shift_code }}
                    </span>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ $shift->date?->format('d/m') }}</span>
                </div>
            @endforeach
        </div>
    </div>
    @endif

    {{-- Navigation buttons --}}
    <div class="grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 dark:border-white/10">
        <a href="{{ \App\Filament\App\Pages\ShiftRoster::getUrl() }}"
           class="flex flex-col items-center gap-1.5 rounded-xl border border-gray-200 p-3 text-center transition hover:border-sky-400 hover:bg-sky-50 dark:border-white/10 dark:hover:border-sky-500/50 dark:hover:bg-sky-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-sky-600 dark:text-sky-400"><path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" /></svg>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-200">Turnos</span>
        </a>
        <a href="{{ \App\Filament\App\Pages\AttendanceRoster::getUrl() }}"
           class="flex flex-col items-center gap-1.5 rounded-xl border border-gray-200 p-3 text-center transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-white/10 dark:hover:border-emerald-500/50 dark:hover:bg-emerald-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-emerald-600 dark:text-emerald-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-200">Asistencias</span>
        </a>
        <a href="{{ \App\Filament\App\Pages\TimeOffRoster::getUrl() }}"
           class="flex flex-col items-center gap-1.5 rounded-xl border border-gray-200 p-3 text-center transition hover:border-amber-400 hover:bg-amber-50 dark:border-white/10 dark:hover:border-amber-500/50 dark:hover:bg-amber-500/10">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5 text-amber-600 dark:text-amber-400"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" /></svg>
            <span class="text-xs font-medium text-gray-700 dark:text-gray-200">Vacaciones</span>
        </a>
    </div>
</div>
