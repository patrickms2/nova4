<?php

namespace App\Filament\App\Pages;

use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class EmployeeMetrics extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationLabel = 'Métricas Empleados';

    protected static ?string $title = 'Métricas de Empleados';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = 3;

    public static function shouldRegisterNavigation(): bool
    {
        //return auth()->user()?->departmentHasService('shifts') || auth()->user()?->isAdmin() ?? false;
        return false;
    }

    protected string $view = 'filament.app.pages.employee-metrics';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public ?int $departmentId = null;

    public string $dateFrom;

    public string $dateTo;

    /**
     * @var list<array{
     *   id: int,
     *   name: string,
     *   dept: string|null,
     *   contract_type: string|null,
     *   shift_preference: string|null,
     *   max_weekends: int|null,
     *   total_working: int,
     *   morning: int,
     *   afternoon: int,
     *   night: int,
     *   weekend: int,
     *   free: int,
     *   free_requested: int,
     *   free_auto_night: int,
     *   vacation: int,
     *   timeoff_days: int,
     * }>
     */
    public array $rows = [];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    public function mount(): void
    {
        $start = now()->startOfMonth();
        $this->dateFrom = $start->toDateString();
        $this->dateTo = $start->copy()->endOfMonth()->toDateString();
        $this->loadMetrics();
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
        ];
    }

    public function updatedDepartmentId(): void
    {
        $this->loadMetrics();
    }

    public function updatedDateFrom(): void
    {
        $this->loadMetrics();
    }

    public function updatedDateTo(): void
    {
        $this->loadMetrics();
    }

    public function loadMetrics(): void
    {
        $from = Carbon::parse($this->dateFrom)->startOfDay();
        $endDate = Carbon::parse($this->dateTo)->endOfDay();

        $query = User::query()
            ->where('status', true)
            ->where(fn($q) => $q->where('role', 'empleado')->orWhere('is_employee', true))
            ->orderBy('name');

        if ($this->departmentId) {
            $query->where('booking_department_id', $this->departmentId);
        }

        $employees = $query->get(['id', 'name', 'booking_department_id', 'contract_type', 'shift_preference', 'max_weekends_per_month']);

        $employeeIds = $employees->pluck('id')->toArray();
        $departments = BookingDepartment::pluck('name', 'id');

        $workingCodes = [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE];

        $shifts = EmployeeShift::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$from->toDateString(), $endDate->toDateString()])
            ->get()
            ->groupBy('employee_id');

        $timeOffs = EmployeeTimeOff::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', EmployeeTimeOff::STATUS_APPROVED)
            ->where(fn($q) => $q
                ->whereBetween('start_date', [$from->toDateString(), $endDate->toDateString()])
                ->orWhereBetween('end_date', [$from->toDateString(), $endDate->toDateString()])
                ->orWhere(fn($q2) => $q2->where('start_date', '<=', $from->toDateString())->where('end_date', '>=', $endDate->toDateString()))
            )
            ->get()
            ->groupBy('employee_id');

        $this->rows = [];

        foreach ($employees as $emp) {
            $empShifts = $shifts->get($emp->id, collect());

            $working = $empShifts->filter(fn($s) => in_array($s->shift_code, $workingCodes))->count();
            $morning = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_MANANA)->count();
            $afternoon = $empShifts->filter(fn($s) => in_array($s->shift_code, [EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO]))->count();
            $night = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_NOCHE)->count();
            $weekend = $empShifts->filter(fn($s) => in_array($s->shift_code, $workingCodes) && $s->date?->isWeekend())->count();
            $free = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE)->count();
            $freeReq = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_REQUESTED)->count();
            $freeNight = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_AUTO_NIGHT)->count();
            $vacation = $empShifts->filter(fn($s) => $s->shift_code === EmployeeShift::SHIFT_VACACIONES)->count();

            $timeOffDays = 0;
            foreach ($timeOffs->get($emp->id, collect()) as $timeOff) {
                $s = max($timeOff->start_date?->toDateString() ?? $from->toDateString(), $from->toDateString());
                $e = min($timeOff->end_date?->toDateString() ?? $endDate->toDateString(), $endDate->toDateString());
                $timeOffDays += Carbon::parse($s)->diffInDays(Carbon::parse($e)) + 1;
            }

            $this->rows[] = [
                'id' => $emp->id,
                'name' => $emp->name,
                'dept' => $emp->booking_department_id ? ($departments[$emp->booking_department_id] ?? null) : null,
                'contract_type' => $emp->contract_type,
                'shift_preference' => $emp->shift_preference,
                'max_weekends' => $emp->max_weekends_per_month,
                'total_working' => $working,
                'morning' => $morning,
                'afternoon' => $afternoon,
                'night' => $night,
                'weekend' => $weekend,
                'free' => $free,
                'free_requested' => $freeReq,
                'free_auto_night' => $freeNight,
                'vacation' => $vacation,
                'timeoff_days' => $timeOffDays,
            ];
        }

        usort($this->rows, fn($a, $b) => $b['weekend'] <=> $a['weekend']);
    }

    public function getDepartmentsProperty(): array
    {
        return BookingDepartment::pluck('name', 'id')->toArray();
    }
}
