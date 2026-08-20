<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class ManageDepartmentVacaciones extends Page
{
    protected static string $resource = BookingDepartmentResource::class;

    public BookingDepartment $record;

    protected static ?string $navigationLabel = 'Vacaciones / Días Libres';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSun;

    protected static ?int $navigationSort = 7;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'department-vacaciones'])->render())
                ->modalHeading('Ayuda - Vacaciones del Departamento')
                ->modalFooterActions([
                    Action::make('close')
                        ->label('Entendido')
                        ->color('primary')
                        ->close(),
                ]),
        ];
    }

    protected string $view = 'filament.app.resources.booking-departments.pages.manage-department-vacaciones';

    public int $month;

    public int $year;

    /** @var array<int, array{day: int, date: string, dow: int, isWeekend: bool, isToday: bool}> */
    public array $days = [];

    /**
     * @var array<string, list<array{employee_id: int, employee_name: string, type: string, color: string}>>
     */
    public array $grid = [];

    /** @var list<array{id: int, name: string}> */
    public array $employees = [];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function getHeading(): string|Htmlable|null
    {
        return $this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Calendario de vacaciones y días libres de los empleados del departamento.';
    }

    public function mount(): void
    {
        $this->month = (int) now()->format('m');
        $this->year  = (int) now()->format('Y');
        $this->loadCalendar();
    }

    public function previousMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->subMonth();
        $this->month = (int) $date->month;
        $this->year  = (int) $date->year;
        $this->loadCalendar();
    }

    public function nextMonth(): void
    {
        $date        = Carbon::createFromDate($this->year, $this->month, 1)->addMonth();
        $this->month = (int) $date->month;
        $this->year  = (int) $date->year;
        $this->loadCalendar();
    }

    public function loadCalendar(): void
    {
        $deptId      = $this->record->id;
        $start       = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $end         = $start->copy()->endOfMonth()->endOfDay();
        $daysInMonth = $start->daysInMonth;

        $this->days = [];
        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date         = Carbon::createFromDate($this->year, $this->month, $d);
            $this->days[] = [
                'day'       => $d,
                'date'      => $date->format('Y-m-d'),
                'dow'       => $date->dayOfWeek,
                'isWeekend' => $date->isWeekend(),
                'isToday'   => $date->isToday(),
            ];
        }

        $employeeModels = User::query()
            ->where('booking_department_id', $deptId)
            ->where('status', true)
            ->where(fn ($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))
            ->orderBy('name')
            ->get(['id', 'name']);

        $this->employees = $employeeModels
            ->map(fn ($e) => ['id' => $e->id, 'name' => $e->name])
            ->toArray();

        $employeeIds = $employeeModels->pluck('id');

        $timeOffs = EmployeeTimeOff::query()
            ->whereIn('employee_id', $employeeIds)
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start->toDateString())->where('end_date', '>=', $end->toDateString()))
            )
            ->where('status', '!=', EmployeeTimeOff::STATUS_DENIED)
            ->with('employee:id,name')
            ->get();

        $shiftVacations = EmployeeShift::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereIn('shift_code', [EmployeeShift::SHIFT_VACACIONES, EmployeeShift::SHIFT_LIBRE])
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->with('employee:id,name')
            ->get();

        $this->grid = [];

        foreach ($this->days as $dayInfo) {
            $dateKey             = $dayInfo['date'];
            $this->grid[$dateKey] = [];

            foreach ($timeOffs as $to) {
                $toStart = $to->start_date?->toDateString() ?? $dateKey;
                $toEnd   = $to->end_date?->toDateString() ?? $dateKey;

                if ($dateKey >= $toStart && $dateKey <= $toEnd) {
                    $color = match ($to->type) {
                        EmployeeTimeOff::TYPE_VACACIONES => 'violet',
                        EmployeeTimeOff::TYPE_BAJA       => 'red',
                        EmployeeTimeOff::TYPE_PERSONAL   => 'amber',
                        default                           => 'gray',
                    };
                    $typeLabel = match ($to->type) {
                        EmployeeTimeOff::TYPE_VACACIONES => 'Vacaciones',
                        EmployeeTimeOff::TYPE_BAJA       => 'Baja',
                        EmployeeTimeOff::TYPE_PERSONAL   => 'Personal',
                        EmployeeTimeOff::TYPE_PERMISO    => 'Permiso',
                        default                           => ucfirst($to->type ?? ''),
                    };
                    $statusSuffix = $to->status === EmployeeTimeOff::STATUS_PENDING ? ' (pend.)' : '';

                    $this->grid[$dateKey][] = [
                        'employee_id'   => $to->employee_id,
                        'employee_name' => $to->employee?->name ?? '—',
                        'type'          => $typeLabel . $statusSuffix,
                        'color'         => $color,
                    ];
                }
            }

            foreach ($shiftVacations as $sv) {
                $svDate = $sv->date?->format('Y-m-d');
                if ($svDate === $dateKey) {
                    $color = $sv->shift_code === EmployeeShift::SHIFT_VACACIONES ? 'violet' : 'green';
                    $typeLabel = $sv->shift_code === EmployeeShift::SHIFT_VACACIONES
                        ? 'Vacaciones'
                        : match ($sv->libre_reason) {
                            EmployeeShift::LIBRE_REASON_REQUESTED  => 'Libre (sol.)',
                            EmployeeShift::LIBRE_REASON_AUTO_NIGHT => 'Libre (noche)',
                            default                                  => 'Libre',
                        };

                    $alreadyPresent = collect($this->grid[$dateKey])->contains(fn ($entry) => $entry['employee_id'] === $sv->employee_id && str_contains($entry['type'], 'Vacaciones'));

                    if (! $alreadyPresent) {
                        $this->grid[$dateKey][] = [
                            'employee_id'   => $sv->employee_id,
                            'employee_name' => $sv->employee?->name ?? '—',
                            'type'          => $typeLabel,
                            'color'         => $color,
                        ];
                    }
                }
            }

            usort($this->grid[$dateKey], fn ($a, $b) => strcmp($a['employee_name'], $b['employee_name']));
        }
    }
}
