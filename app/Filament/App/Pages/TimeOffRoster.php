<?php

namespace App\Filament\App\Pages;

use App\Models\BookingDepartment;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\User;
use App\Services\Hrm\EmployeeTimeOffService;
use App\Services\Hrm\ShiftSwapService;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class TimeOffRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-sun';

    protected static ?string $navigationLabel = 'Cuadrante Vacaciones y Permisos';

    protected static ?string $title = 'Cuadrante Vacaciones y Permisos';

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

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.app.pages.time-off-roster';

    public int $month;

    public int $year;

    public ?int $departmentId = null;

    public ?int $prefillEmployeeId = null;

    public ?string $prefillDate = null;

    /** @var array<int, array{id: int, name: string, code: ?string, avatar: ?string, initials: string, dept: ?string}> */
    public array $employees = [];

    /** @var array<int, array{day: int, dow: int, dowName: string, date: string, isWeekend: bool, isToday: bool}> */
    public array $days = [];

    /** @var array<int, array<int, array{hasTimeOff: bool, id?: int, type?: string, status?: string}>> */
    public array $grid = [];

    /** @var array<string, array{id:int, role:string, partner_name:string, partner_id:int, status:string}> */
    public array $pendingSwapMap = [];

    public array $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public array $typeLabels = [
        EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
        EmployeeTimeOff::TYPE_PERMISO => 'Permiso',
        EmployeeTimeOff::TYPE_PERSONAL => 'Personal',
        EmployeeTimeOff::TYPE_BAJA => 'Baja',
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

        $timeOffs = EmployeeTimeOff::query()
            ->whereIn('employee_id', $employeeModels->pluck('id'))
            ->where(function ($q) use ($start, $end) {
                $q->whereBetween('start_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhereBetween('end_date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
                    ->orWhere(function ($q2) use ($start, $end) {
                        $q2->where('start_date', '<=', $start->format('Y-m-d'))
                            ->where('end_date', '>=', $end->format('Y-m-d'));
                    });
            })
            ->get()
            ->groupBy('employee_id');

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

            $empTimeOffs = $timeOffs->get($emp->id, collect());

            $row = [];
            foreach ($this->days as $dayInfo) {
                $match = $empTimeOffs->first(function ($to) use ($dayInfo) {
                    $d = $dayInfo['date'];
                    return $to->start_date?->format('Y-m-d') <= $d && $to->end_date?->format('Y-m-d') >= $d;
                });

                if ($match) {
                    $cell = [
                        'hasTimeOff' => true,
                        'id' => $match->id,
                        'type' => $match->type,
                        'status' => $match->status,
                    ];
                } else {
                    $cell = ['hasTimeOff' => false];
                }

                $pendingSwap = $this->pendingSwapMap[$emp->id . '-' . $dayInfo['date']] ?? null;

                if ($pendingSwap) {
                    $cell['pendingSwap'] = $pendingSwap;
                }

                $row[] = $cell;
            }

            $this->grid[$emp->id] = $row;
        }
    }

    public function getDepartmentsProperty(): array
    {
        return BookingDepartment::pluck('name', 'id')->toArray();
    }

    public function openForEmployee(int $employeeId): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = null;
        $this->mountAction('addTimeOff');
    }

    public function openForDay(int $employeeId, string $date): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = $date;
        $this->mountAction('addTimeOff');
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
                $recentShifts = \App\Models\EmployeeShift::where('employee_id', $arguments['employeeId'])
                    ->orderByDesc('date')->limit(5)->get();
                $pendingTimeOff = EmployeeTimeOff::where('employee_id', $arguments['employeeId'])
                    ->where('status', EmployeeTimeOff::STATUS_PENDING)->count();
                return view('filament.app.pages.partials.employee-info-modal', compact('emp', 'recentShifts', 'pendingTimeOff'));
            })
            ->modalFooterActions(function (array $arguments): array {
                $id = $arguments['employeeId'];
                return [
                    Action::make('goShifts')
                        ->label('Turnos')
                        ->color('info')
                        ->url(fn() => ShiftRoster::getUrl())
                        ->icon('heroicon-o-calendar-days'),
                    Action::make('goAttendance')
                        ->label('Asistencias')
                        ->color('success')
                        ->url(fn() => AttendanceRoster::getUrl())
                        ->icon('heroicon-o-clock'),
                    Action::make('goTimeOff')
                        ->label('Vacaciones/Permisos')
                        ->color('warning')
                        ->url(fn() => TimeOffRoster::getUrl())
                        ->icon('heroicon-o-sun'),
                ];
            })
            ->modalSubmitAction(false);
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
            Action::make('addTimeOff')
                ->label('Añadir ausencia')
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
                    Select::make('type')
                        ->label('Tipo')
                        ->options([
                            EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
                            EmployeeTimeOff::TYPE_PERMISO => 'Permiso',
                            EmployeeTimeOff::TYPE_PERSONAL => 'Día personal',
                            EmployeeTimeOff::TYPE_BAJA => 'Baja médica',
                        ])
                        ->required()
                        ->default(EmployeeTimeOff::TYPE_VACACIONES),
                    DatePicker::make('start_date')
                        ->label('Desde')
                        ->required()
                        ->default(fn() => $this->prefillDate ?? now()->toDateString()),
                    DatePicker::make('end_date')
                        ->label('Hasta')
                        ->required()
                        ->default(fn() => $this->prefillDate ?? now()->toDateString()),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            EmployeeTimeOff::STATUS_PENDING => 'Pendiente',
                            EmployeeTimeOff::STATUS_APPROVED => 'Aprobado',
                            EmployeeTimeOff::STATUS_DENIED => 'Denegado',
                        ])
                        ->default(EmployeeTimeOff::STATUS_PENDING)
                        ->required(),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->nullable(),
                ])
                ->action(function (array $data): void {
                    app(EmployeeTimeOffService::class)->create([
                        'employee_id' => $data['employee_id'],
                        'user_id' => $data['employee_id'],
                        'booking_department_id' => User::query()->find($data['employee_id'])?->booking_department_id,
                        'type' => $data['type'],
                        'start_date' => $data['start_date'],
                        'end_date' => $data['end_date'],
                        'status' => $data['status'],
                        'notes' => $data['notes'] ?? null,
                        'is_full_day' => true,
                        'reviewed_by' => $data['status'] !== EmployeeTimeOff::STATUS_PENDING ? auth()->id() : null,
                        'reviewed_at' => $data['status'] !== EmployeeTimeOff::STATUS_PENDING ? now() : null,
                    ]);

                    Notification::make()->title('Ausencia registrada')->success()->send();
                    $this->loadRoster();
                }),
        ];
    }

    public function editTimeOffRecordAction(): Action
    {
        return Action::make('editTimeOffRecord')
            ->label('Editar ausencia')
            ->form(function (array $arguments): array {
                $record = EmployeeTimeOff::find($arguments['timeOffId']);

                return [
                    Select::make('type')
                        ->label('Tipo')
                        ->options([
                            EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
                            EmployeeTimeOff::TYPE_PERMISO => 'Permiso',
                            EmployeeTimeOff::TYPE_PERSONAL => 'Día personal',
                            EmployeeTimeOff::TYPE_BAJA => 'Baja médica',
                        ])
                        ->required()
                        ->default($record?->type),
                    DatePicker::make('start_date')
                        ->label('Desde')
                        ->required()
                        ->default($record?->start_date?->format('Y-m-d')),
                    DatePicker::make('end_date')
                        ->label('Hasta')
                        ->required()
                        ->default($record?->end_date?->format('Y-m-d')),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            EmployeeTimeOff::STATUS_PENDING => 'Pendiente',
                            EmployeeTimeOff::STATUS_APPROVED => 'Aprobado',
                            EmployeeTimeOff::STATUS_DENIED => 'Denegado',
                        ])
                        ->required()
                        ->default($record?->status ?? EmployeeTimeOff::STATUS_PENDING),
                    Textarea::make('notes')
                        ->label('Notas')
                        ->nullable()
                        ->default($record?->notes),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $record = EmployeeTimeOff::find($arguments['timeOffId']);
                if (! $record) {
                    return;
                }

                app(EmployeeTimeOffService::class)->update($record, [
                    'type' => $data['type'],
                    'start_date' => $data['start_date'],
                    'end_date' => $data['end_date'],
                    'status' => $data['status'],
                    'notes' => $data['notes'],
                    'reviewed_by' => $data['status'] !== EmployeeTimeOff::STATUS_PENDING ? auth()->id() : null,
                    'reviewed_at' => $data['status'] !== EmployeeTimeOff::STATUS_PENDING ? now() : null,
                ]);

                Notification::make()->title('Ausencia actualizada')->success()->send();
                $this->loadRoster();
            })
            ->extraModalFooterActions(function (Action $action): array {
                $arguments = $action->getArguments();
                $timeOffId = $arguments['timeOffId'] ?? null;

                return [
                    Action::make('deleteTimeOff')
                        ->label('Eliminar ausencia')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function () use ($timeOffId): void {
                            if ($timeOffId) {
                                $record = EmployeeTimeOff::find($timeOffId);
                                if ($record) {
                                    $record->delete();
                                    Notification::make()->title('Ausencia eliminada')->success()->send();
                                    $this->loadRoster();
                                }
                            }
                            $this->unmountAction();
                        }),
                ];
            })
            ->modalHeading('Editar ausencia');
    }

    public function editTimeOff(int $timeOffId): void
    {
        $record = EmployeeTimeOff::find($timeOffId);
        if (!$record) {
            return;
        }

        $this->mountAction('editTimeOffRecord', ['timeOffId' => $timeOffId]);
    }

    public function reviewSwapRequest(int $swapRequestId): void
    {
        if (! ShiftSwapRequest::query()->whereKey($swapRequestId)->exists()) {
            return;
        }

        $this->mountAction('reviewSwapRequestRecord', ['swapRequestId' => $swapRequestId]);
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
                        ->options([$request?->requester_shift_id => $request?->requesterShift ? $request->requesterShift->date?->format('d/m') . ' — ' . \App\Models\EmployeeShift::shiftLabel((string) $request->requesterShift->shift_code) : '—'])
                        ->default($request?->requester_shift_id)
                        ->disabled(),
                    Select::make('target_shift_id')
                        ->label('Turno libre del compañero')
                        ->options([$request?->target_shift_id => $request?->targetShift ? $request->targetShift->date?->format('d/m') . ' — ' . \App\Models\EmployeeShift::shiftLabel((string) $request->targetShift->shift_code) : '—'])
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
