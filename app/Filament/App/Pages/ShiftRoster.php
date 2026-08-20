<?php

namespace App\Filament\App\Pages;

use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\Hrm\ShiftSwapService;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShiftRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Cuadrante Turnos';

    protected static ?string $title = 'Cuadrante de Turnos';

    public static function shouldRegisterNavigation(): bool
    {
        return false;
        //return auth()->user()?->departmentHasService('shifts') || auth()->user()?->isAdmin() ?? false;
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static ?int $navigationSort = 2;

    protected string $view = 'filament.app.pages.shift-roster';

    public int $month;

    public int $year;

    public ?int $departmentId = 16;

    /** @var array<int, array{id: int, name: string, code: ?string, avatar: ?string, initials: string, dept: ?string}> */
    public array $employees = [];

    /** @var array<int, array{day: int, dow: int, dowName: string, date: string, isWeekend: bool, isToday: bool}> */
    public array $days = [];

    /** @var array<int, array<int, array{hasShift: bool, id?: int, code?: string, status?: string, timeoffPending?: bool, timeoffApproved?: bool, timeoffType?: string}>> */
    public array $grid = [];

    /** @var array<int, int> employee_id => count of pending timeoff requests in this month */
    public array $pendingCounts = [];

    /** @var array<string, array{id:int, role:string, partner_name:string, partner_id:int, status:string}> */
    public array $pendingSwapMap = [];

    public ?int $prefillEmployeeId = null;

    public ?string $prefillDate = null;

    public array $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public array $shiftLabels = [
        'M' => 'Mañana',
        'T' => 'Tarde',
        'P' => 'Partido',
        'N' => 'Noche',
        'L' => 'Libre',
        'V' => 'Vacaciones',
        'B' => 'Baja',
        'S' => 'Saliente',
    ];

    public function mount(): void
    {
        $this->month = (int)now()->format('m');
        $this->year = (int)now()->format('Y');
        $this->loadRoster();
    }

    public function previousMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = (int)$date->month;
        $this->year = (int)$date->year;
        $this->loadRoster();
    }

    public function nextMonth(): void
    {
        $date = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = (int)$date->month;
        $this->year = (int)$date->year;
        $this->loadRoster();
    }

    public function updatedDepartmentId(): void
    {
        $this->loadRoster();
    }

    public function loadRoster(): void
    {
        $start = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $end = $start->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $start->daysInMonth;

        $this->days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date = Carbon::createFromDate($this->year, $this->month, $d);
            $this->days[] = [
                'day' => $d,
                'dow' => $date->dayOfWeek,
                'dowName' => $this->dayNames[$date->dayOfWeek],
                'date' => $date->format('Y-m-d'),
                'isWeekend' => $date->isWeekend(),
                'isToday' => $date->isToday(),
            ];
        }

        $employeeQuery = User::query()
            ->where('status', true)
            ->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))
            ->orderBy('name');

        if ($this->departmentId) {
            $employeeQuery->where('booking_department_id', $this->departmentId);
        }

        $employeeModels = $employeeQuery->get(['id', 'name', 'avatar_url', 'booking_department_id', 'employee_code']);

        $shifts = EmployeeShift::query()
            ->whereIn('employee_id', $employeeModels->pluck('id'))
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy('employee_id');

        $timeoffs = EmployeeTimeOff::query()
            ->whereIn('employee_id', $employeeModels->pluck('id'))
            ->whereIn('status', [EmployeeTimeOff::STATUS_PENDING, EmployeeTimeOff::STATUS_APPROVED])
            ->where('start_date', '<=', $end->format('Y-m-d'))
            ->where('end_date', '>=', $start->format('Y-m-d'))
            ->get();

        $timeoffMap = [];
        $pendingCounts = [];
        foreach ($timeoffs as $to) {
            $toStart = Carbon::parse($to->start_date)->max($start);
            $toEnd = Carbon::parse($to->end_date)->min($end);
            for ($d = $toStart->copy(); $d->lte($toEnd); $d->addDay()) {
                $key = $to->employee_id . '-' . $d->format('Y-m-d');
                $timeoffMap[$key] = [
                    'status' => $to->status,
                    'type' => $to->type,
                ];
                if ($to->status === EmployeeTimeOff::STATUS_PENDING) {
                    $pendingCounts[$to->employee_id] = ($pendingCounts[$to->employee_id] ?? 0) + 1;
                }
            }
        }
        $this->pendingCounts = $pendingCounts;

        $departments = BookingDepartment::pluck('name', 'id');

        $this->employees = [];
        $this->grid = [];
        $this->pendingSwapMap = app(ShiftSwapService::class)->buildPendingSwapMap(
            $employeeModels->pluck('id')->map(fn (mixed $id): int => (int) $id)->all(),
            $start,
            $end,
        );

        foreach ($employeeModels as $emp) {
            $name = mb_convert_encoding((string)($emp->name ?? ''), 'UTF-8', 'UTF-8');
            $this->employees[] = [
                'id' => $emp->id,
                'name' => $name,
                'code' => $emp->employee_code,
                'avatar' => $emp->avatar_url,
                'initials' => strtoupper(mb_substr($name, 0, 1)) . strtoupper(mb_substr(mb_strstr($name, ' ') ?: '', 1, 1)),
                'dept' => $emp->booking_department_id ? ($departments[$emp->booking_department_id] ?? null) : null,
            ];

            $empShifts = $shifts->get($emp->id, collect());

            $row = [];
            foreach ($this->days as $dayInfo) {
                $shift = $empShifts->first(fn($s) => $s->date?->format('Y-m-d') === $dayInfo['date']);
                $toKey = $emp->id . '-' . $dayInfo['date'];
                $toInfo = $timeoffMap[$toKey] ?? null;

                $cellData = $shift
                    ? [
                        'hasShift' => true,
                        'id' => $shift->id,
                        'code' => $shift->shift_code,
                        'status' => $shift->status,
                    ]
                    : ['hasShift' => false];

                $pendingSwap = $this->pendingSwapMap[$emp->id . '-' . $dayInfo['date']] ?? null;

                if ($pendingSwap) {
                    $cellData['pendingSwap'] = $pendingSwap;
                }

                if ($toInfo) {
                    $cellData['timeoffPending'] = $toInfo['status'] === EmployeeTimeOff::STATUS_PENDING;
                    $cellData['timeoffApproved'] = $toInfo['status'] === EmployeeTimeOff::STATUS_APPROVED;
                    $cellData['timeoffType'] = $toInfo['type'];
                }

                $row[] = $cellData;
            }
            $this->grid[$emp->id] = $row;
        }
    }

    public function getDepartmentsProperty(): array
    {
        return BookingDepartment::pluck('name', 'id')->toArray();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('empleados')
                ->label('Empleados')
                ->icon('heroicon-s-user')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/employees';
                }),
            Action::make('assignShift')
                ->label('Asignar Turno')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->form([
                    Select::make('employee_id')
                        ->label('Empleado')
                        ->options(fn() => User::where('status', true)
                            ->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))
                            ->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn() => $this->prefillEmployeeId),
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->default(fn() => $this->prefillDate ?? now()->toDateString()),
                    Select::make('shift_code')
                        ->label('Turno')
                        ->required()
                        ->options([
                            EmployeeShift::SHIFT_MANANA => 'Mañana (M)',
                            EmployeeShift::SHIFT_TARDE => 'Tarde (T)',
                            EmployeeShift::SHIFT_PARTIDO => 'Partido (P)',
                            EmployeeShift::SHIFT_NOCHE => 'Noche (N)',
                            EmployeeShift::SHIFT_LIBRE => 'Libre (L)',
                            EmployeeShift::SHIFT_VACACIONES => 'Vacaciones (V)',
                            EmployeeShift::SHIFT_BAJA => 'Baja (B)',
                        ]),
                    Select::make('booking_department_id')
                        ->label('Departamento')
                        ->options(fn() => BookingDepartment::pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            EmployeeShift::STATUS_PLANNED => 'Planificado',
                            EmployeeShift::STATUS_CONFIRMED => 'Confirmado',
                            EmployeeShift::STATUS_LOCKED => 'Bloqueado',
                        ])
                        ->default(EmployeeShift::STATUS_PLANNED)
                        ->required(),
                ])
                ->action(function (array $data): void {
                    EmployeeShift::updateOrCreate(
                        [
                            'employee_id' => $data['employee_id'],
                            'date' => $data['date'],
                        ],
                        [
                            'shift_code' => $data['shift_code'],
                            'booking_department_id' => $data['booking_department_id'] ?? null,
                            'status' => $data['status'],
                        ]
                    );

                    Notification::make()->title('Turno asignado')->success()->send();
                    $this->loadRoster();
                }),

            Action::make('generateRotation')
                ->label('Generar Turnos')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->modalHeading('Generación de Turnos')
                ->modalWidth('3xl')
                ->steps([
                    Step::make('Departamento y Tipo')
                        ->icon('heroicon-o-building-office')
                        ->description('Selecciona departamento y tipo de generación')
                        ->schema([
                            Select::make('booking_department_id')
                                ->label('Departamento')
                                ->options(fn() => BookingDepartment::pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->default(fn() => BookingDepartment::where('slug', 'central')->value('id'))
                                ->live(),
                            Select::make('generation_type')
                                ->label('Tipo de generación')
                                ->required()
                                ->options([
                                    '3+3+1' => 'Distribución 3+3+1 (3M, 3P, 1N por día)',
                                    'M-M-M-M-M-L-L' => 'Mañana L-V, Libre S-D',
                                    'P-P-P-P-P-L-L' => 'Partido L-V, Libre S-D',
                                    'N-N-S-L-L-M-M' => 'Noche 2d → Saliente → Libre 2d → Mañana 2d',
                                    'M-M-N-N-S-L-L' => 'Mañana 2d → Noche 2d → Saliente → Libre 2d',
                                    'M-P-N-S-L-L-L' => 'Rotación diaria M→P→N→S→L×3',
                                    'custom' => 'Personalizado',
                                ])
                                ->default('3+3+1')
                                ->live(),
                            Repeater::make('custom_pattern')
                                ->label('Patrón personalizado (secuencia de días)')
                                ->simple(
                                    Select::make('shift')
                                        ->options([
                                            'M' => 'Mañana', 'P' => 'Partido', 'N' => 'Noche',
                                            'L' => 'Libre', 'V' => 'Vacaciones', 'B' => 'Baja', 'S' => 'Saliente',
                                        ])
                                        ->required()
                                )
                                ->visible(fn($get) => $get('generation_type') === 'custom')
                                ->minItems(1)
                                ->maxItems(31)
                                ->defaultItems(7),
                        ]),
                    Step::make('Empleados')
                        ->icon('heroicon-o-users')
                        ->description('Selecciona los empleados a incluir')
                        ->schema([
                            CheckboxList::make('employee_ids')
                                ->label('Empleados')
                                ->options(function ($get): array {
                                    $deptId = $get('booking_department_id');
                                    $query = User::where('status', true)
                                        ->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true));

                                    if ($deptId) {
                                        $query->where('booking_department_id', $deptId);
                                    }

                                    return $query->orderBy('name')->pluck('name', 'id')->toArray();
                                })
                                ->required()
                                ->columns(2)
                                ->searchable()
                                ->bulkToggleable()
                                ->default(function ($get): array {
                                    $deptId = $get('booking_department_id');
                                    $query = User::where('status', true)
                                        ->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true));

                                    if ($deptId) {
                                        $query->where('booking_department_id', $deptId);
                                    }

                                    return $query->pluck('id')->toArray();
                                }),
                        ]),
                    Step::make('Período y Opciones')
                        ->icon('heroicon-o-calendar')
                        ->description('Configura el mes, fechas y estado')
                        ->schema([
                            Select::make('month')
                                ->label('Mes')
                                ->options([
                                    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                ])
                                ->default(now()->month)
                                ->required()
                                ->live()
                                ->afterStateUpdated(function ($state, $set): void {
                                    if ($state) {
                                        $year = now()->year;
                                        if ((int)$state < now()->month) {
                                            $year++;
                                        }
                                        $start = Carbon::createFromDate($year, (int)$state, 1);
                                        $set('start_date', $start->format('Y-m-d'));
                                        $set('end_date', $start->copy()->endOfMonth()->format('Y-m-d'));
                                    }
                                }),
                            DatePicker::make('start_date')
                                ->label('Desde')
                                ->required()
                                ->default(fn() => Carbon::createFromDate(now()->year, now()->month, 1)->format('Y-m-d')),
                            DatePicker::make('end_date')
                                ->label('Hasta')
                                ->required()
                                ->default(fn() => Carbon::createFromDate(now()->year, now()->month, 1)->endOfMonth()->format('Y-m-d')),
                            Select::make('status')
                                ->label('Estado inicial')
                                ->options([
                                    EmployeeShift::STATUS_PLANNED => 'Planificado',
                                    EmployeeShift::STATUS_CONFIRMED => 'Confirmado',
                                ])
                                ->default(EmployeeShift::STATUS_CONFIRMED)
                                ->required(),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->executeRotationGeneration($data);
                }),

            Action::make('exportCuadrante')
                ->label('Descargar Cuadrante')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function (): StreamedResponse {
                    return $this->exportRosterHtml();
                }),
        ];
    }

    private function executeRotationGeneration(array $data): void
    {
        $generationType = $data['generation_type'];
        $startDate = Carbon::parse($data['start_date']);
        $endDate = Carbon::parse($data['end_date']);
        $employeeIds = $data['employee_ids'];
        $departmentId = $data['booking_department_id'] ?? null;
        $status = $data['status'];

        $this->departmentId = $departmentId;
        $this->month = (int) $startDate->month;
        $this->year = (int) $startDate->year;

        if ($generationType === '3+3+1') {
            $this->executeDistributionGeneration($employeeIds, $startDate, $endDate, $departmentId, $status);

            return;
        }

        if ($generationType === 'custom') {
            $pattern = collect($data['custom_pattern'] ?? [])
                ->pluck('shift')
                ->filter()
                ->values()
                ->toArray();

            if (empty($pattern)) {
                Notification::make()->title('Patrón vacío')->warning()->send();

                return;
            }
        } else {
            $pattern = explode('-', $generationType);
        }

        $patternLength = count($pattern);
        $created = 0;
        $skipped = 0;

        foreach ($employeeIds as $index => $employeeId) {
            $offset = $index % $patternLength;
            $currentDate = $startDate->copy();
            $dayIndex = 0;

            while ($currentDate->lte($endDate)) {
                $shiftCode = $pattern[($dayIndex + $offset) % $patternLength];

                $existing = EmployeeShift::query()
                    ->where('employee_id', $employeeId)
                    ->whereDate('date', $currentDate)
                    ->first();

                if ($existing && $existing->status !== EmployeeShift::STATUS_PLANNED) {
                    $skipped++;
                    $currentDate->addDay();
                    $dayIndex++;

                    continue;
                }

                EmployeeShift::updateOrCreate(
                    [
                        'employee_id' => $employeeId,
                        'date' => $currentDate->format('Y-m-d'),
                    ],
                    [
                        'shift_code' => $shiftCode,
                        'booking_department_id' => $departmentId,
                        'status' => $status,
                    ]
                );

                $created++;
                $currentDate->addDay();
                $dayIndex++;
            }
        }

        Notification::make()
            ->title('Rotación generada')
            ->body("Turnos creados/actualizados: {$created}. Omitidos (confirmados/bloqueados): {$skipped}.")
            ->success()
            ->send();

        $this->loadRoster();
    }

    private function executeDistributionGeneration(array $employeeIds, Carbon $startDate, Carbon $endDate, ?int $departmentId, string $status): void
    {
        $slotsM = 3;
        $slotsP = 3;
        $slotsN = 1;

        if ($departmentId) {
            $deptSchedules = \App\Models\DepartmentShiftSchedule::where('booking_department_id', $departmentId)
                ->where('is_active', true)
                ->get()
                ->keyBy('shift_code');

            if ($deptSchedules->has('M')) {
                $slotsM = $deptSchedules->get('M')->min_employees;
            }
            if ($deptSchedules->has('P')) {
                $slotsP = $deptSchedules->get('P')->min_employees;
            }
            if ($deptSchedules->has('N')) {
                $slotsN = $deptSchedules->get('N')->min_employees;
            }
        }

        $totalSlots = $slotsM + $slotsP + $slotsN;
        $totalEmployees = count($employeeIds);

        $nightCounts = array_fill_keys($employeeIds, 0);
        $workCounts = array_fill_keys($employeeIds, 0);
        $lastShift = array_fill_keys($employeeIds, null);

        $created = 0;
        $skipped = 0;
        $currentDate = $startDate->copy();

        while ($currentDate->lte($endDate)) {
            $dateStr = $currentDate->format('Y-m-d');

            $locked = [];
            $available = [];

            foreach ($employeeIds as $empId) {
                $existing = EmployeeShift::query()
                    ->where('employee_id', $empId)
                    ->whereDate('date', $currentDate)
                    ->first();

                if ($existing && $existing->status !== EmployeeShift::STATUS_PLANNED) {
                    $locked[] = $empId;
                    $skipped++;
                } else {
                    $available[] = $empId;
                }
            }

            $nightPool = collect($available)
                ->filter(fn(int $id): bool => $lastShift[$id] !== 'N')
                ->sortBy(fn(int $id): int => $nightCounts[$id])
                ->values()
                ->toArray();

            $nightAssigned = array_slice($nightPool, 0, min($slotsN, count($nightPool)));
            $remaining = array_values(array_diff($available, $nightAssigned));

            usort($remaining, fn(int $a, int $b): int => $workCounts[$a] <=> $workCounts[$b]);

            $mAssigned = array_slice($remaining, 0, min($slotsM, count($remaining)));
            $remaining = array_values(array_diff($remaining, $mAssigned));

            $pAssigned = array_slice($remaining, 0, min($slotsP, count($remaining)));
            $remaining = array_values(array_diff($remaining, $pAssigned));

            $assignments = [];
            foreach ($nightAssigned as $empId) {
                $assignments[$empId] = 'N';
            }
            foreach ($mAssigned as $empId) {
                $assignments[$empId] = 'M';
            }
            foreach ($pAssigned as $empId) {
                $assignments[$empId] = 'P';
            }
            foreach ($remaining as $empId) {
                $assignments[$empId] = 'L';
            }

            foreach ($assignments as $empId => $shiftCode) {
                EmployeeShift::updateOrCreate(
                    ['employee_id' => $empId, 'date' => $dateStr],
                    [
                        'shift_code' => $shiftCode,
                        'booking_department_id' => $departmentId,
                        'status' => $status,
                    ]
                );

                $lastShift[$empId] = $shiftCode;
                if ($shiftCode === 'N') {
                    $nightCounts[$empId]++;
                }
                if (in_array($shiftCode, ['M', 'P', 'N'], true)) {
                    $workCounts[$empId]++;
                }
                $created++;
            }

            $currentDate->addDay();
        }

        Notification::make()
            ->title('Distribución 3+3+1 generada')
            ->body("Turnos creados/actualizados: {$created}. Omitidos (confirmados/bloqueados): {$skipped}.")
            ->success()
            ->send();

        $this->loadRoster();
    }

    private function exportRosterHtml(): StreamedResponse
    {
        $monthName = $this->monthNames[$this->month] ?? '';
        $filename = "cuadrante-{$monthName}-{$this->year}.html";

        $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
        $shiftColors = [
            'M' => 'background:#dbeafe;color:#1e40af;',
            'T' => 'background:#fef3c7;color:#92400e;',
            'P' => 'background:#fef9c3;color:#713f12;',
            'N' => 'background:#e0e7ff;color:#3730a3;',
            'L' => 'background:#d1fae5;color:#065f46;',
            'V' => 'background:#ede9fe;color:#5b21b6;',
            'B' => 'background:#fee2e2;color:#991b1b;',
            'S' => 'background:#f3f4f6;color:#374151;',
        ];

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>Cuadrante ' . e($monthName) . ' ' . $this->year . '</title>';
        $html .= '<style>';
        $html .= 'body{font-family:Arial,Helvetica,sans-serif;font-size:11px;margin:12px;color:#111;}';
        $html .= 'h2{text-align:center;margin:0 0 10px;font-size:16px;}';
        $html .= 'table{border-collapse:collapse;width:100%;}';
        $html .= 'th,td{border:1px solid #d1d5db;padding:3px 4px;text-align:center;white-space:nowrap;}';
        $html .= 'th{background:#f9fafb;font-size:10px;}';
        $html .= 'td.emp{text-align:left;font-weight:600;min-width:120px;background:#f9fafb;}';
        $html .= '.cell{display:inline-block;width:24px;height:20px;line-height:20px;border-radius:4px;font-weight:700;font-size:10px;text-align:center;}';
        $html .= '.weekend{background:#fef2f2;}';
        $html .= '.legend{display:flex;gap:8px;justify-content:center;margin:8px 0;font-size:10px;}';
        $html .= '.legend span{padding:2px 8px;border-radius:4px;font-weight:700;}';
        $html .= '@media print{body{margin:0;}@page{size:landscape;margin:8mm;}}';
        $html .= '</style></head><body>';
        $html .= '<h2>Cuadrante de Turnos — ' . e($monthName) . ' ' . $this->year . '</h2>';

        $deptName = $this->departmentId
            ? (BookingDepartment::find($this->departmentId)?->name ?? '')
            : 'Todos los departamentos';

        $html .= '<div class="legend">';
        foreach (['M' => 'Mañana', 'T' => 'Tarde', 'P' => 'Partido', 'N' => 'Noche', 'L' => 'Libre', 'V' => 'Vacaciones', 'B' => 'Baja', 'S' => 'Saliente'] as $code => $label) {
            $html .= '<span style="' . ($shiftColors[$code] ?? '') . '">' . $code . ' ' . $label . '</span>';
        }
        $html .= '</div>';
        $html .= '<p style="text-align:center;font-size:11px;color:#6b7280;margin:0 0 6px;">' . e($deptName) . '</p>';

        $workingCodes = ['M', 'T', 'P', 'N'];

        $html .= '<table><thead><tr><th>Empleado</th>';
        foreach ($this->days as $dayInfo) {
            $cls = $dayInfo['isWeekend'] ? ' class="weekend"' : '';
            $html .= '<th' . $cls . '>' . $dayNames[$dayInfo['dow']] . '<br>' . $dayInfo['day'] . '</th>';
        }
        $html .= '<th style="background:#f9fafb;">M</th><th style="background:#fef3c7;">T</th><th style="background:#e0e7ff;">N</th><th style="background:#fef2f2;">F.Sem</th><th style="background:#d1fae5;">L</th></tr></thead><tbody>';

        foreach ($this->employees as $emp) {
            $empGrid = $this->grid[$emp['id']] ?? [];
            $countM = $countT = $countN = $countWknd = $countL = 0;
            foreach ($empGrid as $i => $cell) {
                if (!($cell['hasShift'] ?? false)) {
                    continue;
                }
                $code = $cell['code'] ?? '';
                if ($code === 'M') {
                    $countM++;
                }
                if (in_array($code, ['T', 'P'], true)) {
                    $countT++;
                }
                if ($code === 'N') {
                    $countN++;
                }
                if (in_array($code, $workingCodes, true) && isset($this->days[$i]) && $this->days[$i]['isWeekend']) {
                    $countWknd++;
                }
                if ($code === 'L') {
                    $countL++;
                }
            }

            $html .= '<tr><td class="emp">' . e($emp['name']) . '</td>';
            foreach ($this->days as $i => $dayInfo) {
                $cell = $empGrid[$i] ?? ['hasShift' => false];
                $wknd = $dayInfo['isWeekend'] ? ' class="weekend"' : '';
                if ($cell['hasShift']) {
                    $code = $cell['code'] ?? 'L';
                    $style = $shiftColors[$code] ?? '';
                    $html .= '<td' . $wknd . '><span class="cell" style="' . $style . '">' . $code . '</span></td>';
                } else {
                    $html .= '<td' . $wknd . '></td>';
                }
            }
            $html .= '<td style="font-weight:700;background:#dbeafe;color:#1e40af;">' . $countM . '</td>';
            $html .= '<td style="font-weight:700;background:#fef3c7;color:#92400e;">' . $countT . '</td>';
            $html .= '<td style="font-weight:700;background:#e0e7ff;color:#3730a3;">' . $countN . '</td>';
            $html .= '<td style="font-weight:700;background:#fef2f2;color:#9a3412;">' . $countWknd . '</td>';
            $html .= '<td style="font-weight:700;background:#d1fae5;color:#065f46;">' . $countL . '</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        $html .= '<script>window.onload=function(){window.print();}</script>';
        $html .= '</body></html>';

        return response()->streamDownload(function () use ($html): void {
            echo $html;
        }, $filename, ['Content-Type' => 'text/html']);
    }

    public function showEmployeeInfo(int $employeeId): void
    {
        $this->mountAction('employeeInfo', ['employeeId' => $employeeId]);
    }

    public function employeeInfoAction(): Action
    {
        return Action::make('employeeInfo')
            ->label('Ficha del empleado')
            ->modalHeading(function (array $arguments): string {
                $emp = User::find($arguments['employeeId']);
                return $emp?->name ?? 'Empleado';
            })
            ->modalContent(function (array $arguments): \Illuminate\Contracts\View\View {
                $emp = User::with(['bookingDepartment'])->find($arguments['employeeId']);
                $recentShifts = EmployeeShift::where('employee_id', $arguments['employeeId'])
                    ->orderByDesc('date')->limit(5)->get();
                $pendingTimeOff = EmployeeTimeOff::where('employee_id', $arguments['employeeId'])
                    ->where('status', EmployeeTimeOff::STATUS_PENDING)->count();
                return view('filament.app.pages.partials.employee-info-modal', compact('emp', 'recentShifts', 'pendingTimeOff'));
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar');
    }

    public function assignShiftForEmployee(int $employeeId): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = null;
        $this->mountAction('assignShift');
    }

    public function openForDay(int $employeeId, string $date): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = $date;
        $this->mountAction('assignShift');
    }

    public function openCoverageSwap(int $employeeId, string $date): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = $date;
        $this->mountAction('createCoverageSwap', [
            'employeeId' => $employeeId,
            'date' => $date,
        ]);
    }

    public function editShift(int $shiftId): void
    {
        $shift = EmployeeShift::find($shiftId);
        if (!$shift) {
            return;
        }

        $this->mountAction('editShiftRecord', ['shiftId' => $shiftId]);
    }

    public function reviewSwapRequest(int $swapRequestId): void
    {
        if (! ShiftSwapRequest::query()->whereKey($swapRequestId)->exists()) {
            return;
        }

        $this->mountAction('reviewSwapRequestRecord', ['swapRequestId' => $swapRequestId]);
    }

    public function editShiftRecordAction(): Action
    {
        return Action::make('editShiftRecord')
            ->label('Editar turno')
            ->form(function (array $arguments): array {
                $shift = EmployeeShift::find($arguments['shiftId']);

                return [
                    Select::make('shift_code')
                        ->label('Turno')
                        ->required()
                        ->options([
                            EmployeeShift::SHIFT_MANANA => 'Mañana (M)',
                            EmployeeShift::SHIFT_PARTIDO => 'Partido (P)',
                            EmployeeShift::SHIFT_NOCHE => 'Noche (N)',
                            EmployeeShift::SHIFT_LIBRE => 'Libre (L)',
                            EmployeeShift::SHIFT_VACACIONES => 'Vacaciones (V)',
                            EmployeeShift::SHIFT_BAJA => 'Baja (B)',
                            EmployeeShift::SHIFT_SALIENTE => 'Saliente (S)',
                        ])
                        ->default($shift?->shift_code),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            EmployeeShift::STATUS_PLANNED => 'Planificado',
                            EmployeeShift::STATUS_CONFIRMED => 'Confirmado',
                            EmployeeShift::STATUS_LOCKED => 'Bloqueado',
                        ])
                        ->default($shift?->status ?? EmployeeShift::STATUS_PLANNED),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->nullable()
                        ->default($shift?->notes),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $shift = EmployeeShift::find($arguments['shiftId']);
                if (!$shift) {
                    return;
                }

                $shift->update([
                    'shift_code' => $data['shift_code'],
                    'status' => $data['status'],
                    'notes' => $data['notes'],
                ]);

                Notification::make()->title('Turno actualizado')->success()->send();
                $this->loadRoster();
            })
            ->extraModalFooterActions(function (Action $action): array {
                $arguments = $action->getArguments();
                $shiftId = $arguments['shiftId'] ?? null;

                return [
                    Action::make('deleteShift')
                        ->label('Eliminar turno')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function () use ($shiftId): void {
                            if ($shiftId) {
                                $shift = EmployeeShift::find($shiftId);
                                if ($shift) {
                                    $shift->delete();
                                    Notification::make()->title('Turno eliminado')->success()->send();
                                    $this->loadRoster();
                                }
                            }
                            $this->unmountAction();
                        }),
                ];
            })
            ->modalHeading('Editar turno');
    }

    public function createCoverageSwapAction(): Action
    {
        return Action::make('createCoverageSwap')
            ->label('Cubrir turno')
            ->form(function (array $arguments): array {
                $employeeId = (int) ($arguments['employeeId'] ?? $this->prefillEmployeeId);
                $date = (string) ($arguments['date'] ?? $this->prefillDate ?? now()->toDateString());
                $employee = User::query()->find($employeeId);
                $departmentId = (int) ($employee?->booking_department_id ?? $this->departmentId);
                $requesterShiftOptions = app(ShiftSwapService::class)->getRequesterShiftOptions($employeeId, $date, true);

                return [
                    Select::make('requester_user_id')
                        ->label('Empleado')
                        ->options($employee ? [$employeeId => $employee->name] : [])
                        ->default($employeeId)
                        ->disabled(),
                    DatePicker::make('swap_date')
                        ->label('Fecha')
                        ->default($date)
                        ->disabled(),
                    Select::make('requester_shift_id')
                        ->label('Turno a cubrir')
                        ->options($requesterShiftOptions)
                        ->default((int) array_key_first($requesterShiftOptions))
                        ->required()
                        ->live(),
                    Select::make('target_user_id')
                        ->label('Empleado intercambio')
                        ->options(fn (callable $get): array => app(ShiftSwapService::class)->getAvailableTargetsForDate(
                            requesterUserId: $employeeId,
                            swapDate: (string) ($get('swap_date') ?: $date),
                            departmentId: $departmentId,
                        ))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->afterStateUpdated(function ($state, Set $set) use ($date): void {
                            if (! filled($state)) {
                                $set('target_shift_id', null);

                                return;
                            }

                            $options = app(ShiftSwapService::class)->getAvailableTargetShifts(
                                targetUserId: (int) $state,
                                swapDate: $date,
                            );

                            $set('target_shift_id', $options !== [] ? (int) array_key_first($options) : null);
                        })
                        ->live(),
                    Select::make('target_shift_id')
                        ->label('Turno libre para cubrir')
                        ->options(fn (callable $get): array => filled($get('target_user_id'))
                            ? app(ShiftSwapService::class)->getAvailableTargetShifts(
                                targetUserId: (int) $get('target_user_id'),
                                swapDate: $date,
                            )
                            : [])
                        ->required()
                        ->searchable(),
                    Textarea::make('requester_notes')
                        ->label('Motivo')
                        ->default('Cobertura por permiso aprobado')
                        ->nullable(),
                    Textarea::make('review_notes')
                        ->label('Notas de aprobación')
                        ->nullable(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $employeeId = (int) ($arguments['employeeId'] ?? $this->prefillEmployeeId);
                $date = (string) ($arguments['date'] ?? $this->prefillDate ?? now()->toDateString());
                $employee = User::query()->find($employeeId);

                if (! $employee) {
                    return;
                }

                $request = app(ShiftSwapService::class)->createSwapRequest(
                    requesterUserId: $employeeId,
                    requesterShiftId: (int) $data['requester_shift_id'],
                    targetUserId: (int) $data['target_user_id'],
                    targetShiftId: (int) $data['target_shift_id'],
                    swapDate: $date,
                    departmentId: (int) ($employee->booking_department_id ?? $this->departmentId),
                    requesterNotes: $data['requester_notes'] ?? null,
                    type: ShiftSwapRequest::TYPE_COVER,
                );

                app(ShiftSwapService::class)->approveRequest(
                    request: $request,
                    reviewedBy: (int) auth()->id(),
                    reviewNotes: $data['review_notes'] ?? null,
                );

                Notification::make()->title('Cobertura aprobada y notificada')->success()->send();
                $this->loadRoster();
            })
            ->modalHeading('Cubrir turno con intercambio')
            ->modalSubmitActionLabel('Aprobar intercambio');
    }

    public function reviewSwapRequestRecordAction(): Action
    {
        return Action::make('reviewSwapRequestRecord')
            ->label('Revisar intercambio')
            ->form(function (array $arguments): array {
                $request = ShiftSwapRequest::query()
                    ->with(['requester', 'target', 'requesterShift', 'targetShift'])
                    ->find($arguments['swapRequestId']);

                return [
                    Select::make('requester_user_id')
                        ->label('Solicitante')
                        ->options([$request?->requester_user_id => $request?->requester?->name])
                        ->default($request?->requester_user_id)
                        ->disabled(),
                    Select::make('target_user_id')
                        ->label('Compañero')
                        ->options([$request?->target_user_id => $request?->target?->name])
                        ->default($request?->target_user_id)
                        ->disabled(),
                    DatePicker::make('swap_date')
                        ->label('Fecha')
                        ->default($request?->swap_date?->format('Y-m-d'))
                        ->disabled(),
                    Select::make('requester_shift_id')
                        ->label('Turno solicitado')
                        ->options([$request?->requester_shift_id => $request?->requesterShift ? $request->requesterShift->date?->format('d/m') . ' — ' . EmployeeShift::shiftLabel((string) $request->requesterShift->shift_code) : '—'])
                        ->default($request?->requester_shift_id)
                        ->disabled(),
                    Select::make('target_shift_id')
                        ->label('Turno libre del compañero')
                        ->options([$request?->target_shift_id => $request?->targetShift ? $request->targetShift->date?->format('d/m') . ' — ' . EmployeeShift::shiftLabel((string) $request->targetShift->shift_code) : '—'])
                        ->default($request?->target_shift_id)
                        ->disabled(),
                    Textarea::make('requester_notes')
                        ->label('Notas del solicitante')
                        ->default($request?->requester_notes)
                        ->disabled(),
                    Textarea::make('review_notes')
                        ->label('Notas de revisión')
                        ->default($request?->review_notes)
                        ->nullable(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $request = ShiftSwapRequest::query()->find($arguments['swapRequestId']);

                if (! $request) {
                    return;
                }

                app(ShiftSwapService::class)->approveRequest(
                    request: $request,
                    reviewedBy: (int) auth()->id(),
                    reviewNotes: $data['review_notes'] ?? null,
                );

                Notification::make()->title('Intercambio aprobado')->success()->send();
                $this->loadRoster();
            })
            ->extraModalFooterActions(function (Action $action): array {
                $arguments = $action->getArguments();
                $swapRequestId = $arguments['swapRequestId'] ?? null;

                return [
                    Action::make('denySwapRequest')
                        ->label('Denegar')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (array $data) use ($swapRequestId): void {
                            if (! $swapRequestId) {
                                return;
                            }

                            $request = ShiftSwapRequest::query()->find($swapRequestId);

                            if (! $request) {
                                return;
                            }

                            app(ShiftSwapService::class)->denyRequest(
                                request: $request,
                                reviewedBy: (int) auth()->id(),
                                reviewNotes: $data['review_notes'] ?? null,
                            );

                            Notification::make()->title('Intercambio denegado')->warning()->send();
                            $this->loadRoster();
                            $this->unmountAction();
                        }),
                ];
            })
            ->modalHeading('Revisar intercambio')
            ->modalSubmitActionLabel('Aprobar');
    }
}
