<?php

namespace App\Filament\App\Pages;

use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\Taxi\Attendance;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;

class AttendanceRoster extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-table-cells';

    protected static ?string $navigationLabel = 'Cuadrante Asistencias';

    protected static ?string $title = 'Registro de Asistencias';

    public static function shouldRegisterNavigation(): bool
    {
        //return auth()->user()?->departmentHasService('attendance') || auth()->user()?->isAdmin() ?? false;
        return false;   
    }

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.app.pages.attendance-roster';

    public int $month;

    public int $year;

    public ?int $departmentId = null;

    public array $employees = [];

    public array $days = [];

    public array $grid = [];

    public ?int $prefillEmployeeId = null;

    public ?string $prefillDate = null;

    public array $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
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

        $attendances = Attendance::query()
            ->whereIn('usuario_id', $employeeModels->pluck('id'))
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy('usuario_id');

        $this->employees = [];
        $this->grid = [];

        foreach ($employeeModels as $emp) {
            $name = mb_convert_encoding((string)($emp->name ?? ''), 'UTF-8', 'UTF-8');
            $this->employees[] = [
                'id' => $emp->id,
                'name' => $name,
                'code' => $emp->employee_code,
                'avatar' => $emp->avatar_url,
                'initials' => strtoupper(mb_substr($name, 0, 1)) . strtoupper(mb_substr(mb_strstr($name, ' ') ?: '', 1, 1)),
            ];

            $empAttendances = $attendances->get($emp->id, collect());

            $row = [];
            foreach ($this->days as $dayInfo) {
                $att = $empAttendances->first(fn($a) => $a->date?->format('Y-m-d') === $dayInfo['date']);
                if ($att) {
                    $checkIn = $att->startDate?->format('H:i') ?? null;
                    $checkOut = $att->endDate?->format('H:i') ?? null;
                    $status = $att->status ?? 'presente';

                    $row[] = [
                        'id' => $att->id,
                        'in' => $checkIn,
                        'out' => $checkOut,
                        'status' => $status,
                        'duration' => $att->duration ? round($att->duration / 60, 1) : null,
                        'hasRecord' => true,
                    ];
                } else {
                    $row[] = ['hasRecord' => false];
                }
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
            Action::make('empleados')
                ->label('Registros E/S')
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

                    return '/app/team/' . $tenantId . '/attendances/attendances';
                }),
            Action::make('register')
                ->label('Registrar E/S')
                ->icon('heroicon-o-clock')
                ->color('primary')
                ->form([
                    Select::make('usuario_id')
                        ->label('Empleado')
                        ->options(fn() => User::where('status', true)->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))->pluck('name', 'id'))
                        ->required()
                        ->searchable()
                        ->preload()
                        ->default(fn() => $this->prefillEmployeeId),
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->default(fn() => $this->prefillDate ?? now()->toDateString()),
                    TimePicker::make('startDate')
                        ->label('Entrada')
                        ->required()
                        ->seconds(false),
                    TimePicker::make('endDate')
                        ->label('Salida')
                        ->seconds(false),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'presente' => 'Presente',
                            'ausente' => 'Ausente',
                            'tarde' => 'Tarde',
                            'temprano' => 'Salida temprana',
                            'completo' => 'Completo',
                            'pendiente' => 'Pendiente',
                        ])
                        ->default('presente')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    $startTime = $data['startDate'] ? Carbon::parse($data['startDate'])->format('H:i') : null;
                    $endTime = $data['endDate'] ? Carbon::parse($data['endDate'])->format('H:i') : null;

                    Attendance::updateOrCreate(
                        [
                            'usuario_id' => $data['usuario_id'],
                            'date' => $data['date'],
                        ],
                        [
                            'startDate' => $startTime,
                            'endDate' => $endTime,
                            'status' => $data['status'],
                        ]
                    );

                    Notification::make()->title('Registro guardado')->success()->send();
                    $this->loadRoster();
                }),
        ];
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

    public function registerForEmployee(int $employeeId): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = null;
        $this->mountAction('register');
    }

    public function openForDay(int $employeeId, string $date): void
    {
        $this->prefillEmployeeId = $employeeId;
        $this->prefillDate = $date;
        $this->mountAction('register');
    }

    public function editAttendance(int $attendanceId): void
    {
        $att = Attendance::find($attendanceId);
        if (!$att) {
            return;
        }

        $this->mountAction('editRecord', ['attendanceId' => $attendanceId]);
    }

    public function editRecordAction(): Action
    {
        return Action::make('editRecord')
            ->label('Editar registro')
            ->form(function (array $arguments): array {
                $att = Attendance::find($arguments['attendanceId']);

                return [
                    TimePicker::make('startDate')
                        ->label('Entrada')
                        ->seconds(false)
                        ->default($att?->startDate),
                    TimePicker::make('endDate')
                        ->label('Salida')
                        ->seconds(false)
                        ->default($att?->endDate),
                    Select::make('status')
                        ->label('Estado')
                        ->options([
                            'presente' => 'Presente',
                            'ausente' => 'Ausente',
                            'tarde' => 'Tarde',
                            'temprano' => 'Salida temprana',
                            'completo' => 'Completo',
                            'pendiente' => 'Pendiente',
                        ])
                        ->default($att?->status ?? 'presente'),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $att = Attendance::find($arguments['attendanceId']);
                if (!$att) {
                    return;
                }

                $startTime = $data['startDate'] ? Carbon::parse($data['startDate'])->format('H:i') : null;
                $endTime = $data['endDate'] ? Carbon::parse($data['endDate'])->format('H:i') : null;

                $att->update([
                    'startDate' => $startTime,
                    'endDate' => $endTime,
                    'status' => $data['status'],
                ]);

                Notification::make()->title('Registro actualizado')->success()->send();
                $this->loadRoster();
            })
            ->extraModalFooterActions(function (Action $action): array {
                $arguments = $action->getArguments();
                $attendanceId = $arguments['attendanceId'] ?? null;

                return [
                    Action::make('deleteRecord')
                        ->label('Eliminar registro')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function () use ($attendanceId): void {
                            if ($attendanceId) {
                                $att = Attendance::find($attendanceId);
                                if ($att) {
                                    $att->delete();
                                    Notification::make()->title('Registro eliminado')->success()->send();
                                    $this->loadRoster();
                                }
                            }
                            $this->unmountAction();
                        }),
                ];
            })
            ->modalHeading('Editar registro de asistencia');
    }
}
