<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;

class ManageDepartmentCalendario extends Page
{
    protected static string $resource = BookingDepartmentResource::class;

    public BookingDepartment $record;

    protected static ?string $navigationLabel = 'Calendario Empleados';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 6;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('help')
                ->label('Ayuda')
                ->icon('heroicon-o-question-mark-circle')
                ->color('gray')
                ->modalContent(fn (): string => view('components.employee-help-popup-content', ['page' => 'department-calendario'])->render())
                ->modalHeading('Ayuda - Calendario del Departamento')
                ->modalFooterActions([
                    Action::make('close')
                        ->label('Entendido')
                        ->color('primary')
                        ->close(),
                ]),
        ];
    }

    protected string $view = 'filament.app.resources.booking-departments.pages.manage-department-calendario';

    public int $month;

    public int $year;

    public ?string $filterShift = null;

    /** @var array<int, array{day: int, date: string, dow: int, isWeekend: bool, isToday: bool}> */
    public array $days = [];

    /** @var array<string, array<int, list<array{id: int, name: string, code: string}>>> */
    public array $grid = [];

    /** @var array{avg_morning: float, avg_afternoon: float, avg_night: float, avg_weekend: float} */
    public array $averages = [];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public array $dayNames = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];

    public function getHeading(): string|Htmlable|null
    {
        return $this->record->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Calendario de empleados asignados por turno.';
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

    public function updatedFilterShift(): void
    {
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
            $date          = Carbon::createFromDate($this->year, $this->month, $d);
            $this->days[]  = [
                'day'       => $d,
                'date'      => $date->format('Y-m-d'),
                'dow'       => $date->dayOfWeek,
                'isWeekend' => $date->isWeekend(),
                'isToday'   => $date->isToday(),
            ];
        }

        $workingCodes = [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE];
        $filterCodes  = $this->filterShift
            ? [$this->filterShift]
            : $workingCodes;

        $shifts = EmployeeShift::query()
            ->with('employee:id,name')
            ->where('booking_department_id', $deptId)
            ->whereIn('shift_code', $filterCodes)
            ->whereBetween('date', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->groupBy(fn ($s) => $s->date?->format('Y-m-d'));

        $this->grid = [];
        foreach ($this->days as $dayInfo) {
            $dateKey             = $dayInfo['date'];
            $dayShifts           = $shifts->get($dateKey, collect());
            $this->grid[$dateKey] = [];

            foreach ($workingCodes as $code) {
                if ($this->filterShift && $this->filterShift !== $code) {
                    continue;
                }

                $this->grid[$dateKey][$code] = $dayShifts
                    ->filter(fn ($s) => $s->shift_code === $code)
                    ->map(fn ($s) => [
                        'id'   => $s->employee_id,
                        'name' => $s->employee?->name ?? '—',
                        'code' => $s->shift_code,
                    ])
                    ->values()
                    ->toArray();
            }
        }

        $this->buildAverages($deptId, $start->format('Y-m-d'), $end->format('Y-m-d'));
    }

    private function buildAverages(int $deptId, string $start, string $end): void
    {
        $allShifts = EmployeeShift::query()
            ->where('booking_department_id', $deptId)
            ->whereBetween('date', [$start, $end])
            ->get();

        $daysInMonth   = Carbon::parse($start)->daysInMonth;
        $weekendDays   = collect(range(0, $daysInMonth - 1))
            ->filter(fn ($i) => Carbon::parse($start)->addDays($i)->isWeekend())
            ->count();
        $weekdayDays   = $daysInMonth - $weekendDays;

        $morningPerDay    = $daysInMonth > 0
            ? round($allShifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_MANANA)->count() / $daysInMonth, 1)
            : 0;
        $afternoonPerDay  = $daysInMonth > 0
            ? round($allShifts->filter(fn ($s) => in_array($s->shift_code, [EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO]))->count() / $daysInMonth, 1)
            : 0;
        $nightPerDay      = $daysInMonth > 0
            ? round($allShifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_NOCHE)->count() / $daysInMonth, 1)
            : 0;
        $weekendWorking   = $allShifts->filter(fn ($s) => in_array($s->shift_code, [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE]) && $s->date?->isWeekend())->count();
        $avgWeekend       = $weekendDays > 0 ? round($weekendWorking / $weekendDays, 1) : 0;

        $this->averages = [
            'avg_morning'   => $morningPerDay,
            'avg_afternoon' => $afternoonPerDay,
            'avg_night'     => $nightPerDay,
            'avg_weekend'   => $avgWeekend,
        ];
    }
}
