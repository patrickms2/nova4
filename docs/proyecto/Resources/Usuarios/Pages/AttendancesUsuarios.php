<?php

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Enums\UserType;
use App\Filament\App\Resources\Usuarios\Attendances\Attendances\AttendanceResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Usuario;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Schema as Form;
use Illuminate\Support\Carbon;

class AttendancesUsuarios extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = AttendanceResource::class;
    protected static ?string $title = 'View Monthly Attendance';

    public ?string $selectedMonth = null;
    public ?string $selectedYear = null;
    public array $attendanceData = [];
    public array $dates = [];
    public bool $showAttendanceTable = false;
    public int $currentPage = 1;
    public int $perPage = 10;
    public int $totalEmployees = 0;
    public int $totalPages = 0;
    public ?array $selectedEmployees = null;
    public bool $isEmployeeUser = true;

    public function mount(): void
    {
        // Check if current user is an employee
        $currentUser = auth()->user();
        $this->isEmployeeUser = true;

        $formData = [
            'month' => now()->format('m'),
            'year' => now()->format('Y'),
        ];

        // If user is an employee, auto-select their employee record
        $this->selectedEmployees = [auth()->id()];
        $formData['usuarios'] = [auth()->id()];

        $this->form->fill($formData);

        // Automatically load current month's attendance data
        $this->loadAttendanceData(now()->format('m'), now()->format('Y'));
    }

    public function form(Form $form): Form
    {
        $schema = [
            Select::make('month')
                ->label('Month')
                ->options([
                    '01' => 'January',
                    '02' => 'February',
                    '03' => 'March',
                    '04' => 'April',
                    '05' => 'May',
                    '06' => 'June',
                    '07' => 'July',
                    '08' => 'August',
                    '09' => 'September',
                    '10' => 'October',
                    '11' => 'November',
                    '12' => 'December',
                ])
                ->required()
                ->native(false),
            Select::make('year')
                ->label('Year')
                ->options(collect(range(now()->year - 5, now()->year + 1))->mapWithKeys(fn($year) => [$year => $year]))
                ->required()
                ->native(false),
        ];

        // Add employee filter only for non-employee users
        if (!$this->isEmployeeUser) {
            $schema[] = Select::make('usuario')
                ->label('Select Usuario')
                ->options(Usuario::get()->mapWithKeys(function ($employee) {
                    $name = $employee->nombre;
                    return [$employee->id => $name];
                }))
                ->multiple()
                ->searchable()
                ->preload()
                ->native(false)
                ->helperText('Leave empty to show all employees');
        }

        return $form->schema($schema);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('viewAttendance')
                ->label('View Monthly Attendance')
                ->icon('heroicon-o-calendar')
                ->color('primary')
                ->form(function () {
                    $modalSchema = [
                        Select::make('month')
                            ->label('Month')
                            ->options([
                                '01' => 'January',
                                '02' => 'February',
                                '03' => 'March',
                                '04' => 'April',
                                '05' => 'May',
                                '06' => 'June',
                                '07' => 'July',
                                '08' => 'August',
                                '09' => 'September',
                                '10' => 'October',
                                '11' => 'November',
                                '12' => 'December',
                            ])
                            ->required()
                            ->default(now()->format('m'))
                            ->native(false),
                        Select::make('year')
                            ->label('Year')
                            ->options(collect(range(now()->year - 5, now()->year + 1))->mapWithKeys(fn($year) => [$year => $year]))
                            ->required()
                            ->default(now()->format('Y'))
                            ->native(false),
                    ];

                    // Add employee filter only for non-employee users
                    if (!$this->isEmployeeUser) {
                        $modalSchema[] = Select::make('usuarios')
                            ->label('Select Usuario')
                            ->options(Employee::active()->get()->mapWithKeys(function ($employee) {
                                $name = $employee->nombre;
                                return [$employee->id => $name];
                            }))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->helperText('Leave empty to show all employees');
                    }

                    return $modalSchema;
                })
                ->action(function (array $data): void {
                    $this->selectedEmployees = $data['usuarios'] ?? null;
                    $this->loadAttendanceData($data['month'], $data['year']);
                })
                ->modalHeading('Select Month and Year')
                ->modalDescription('Choose the month and year to view attendance data.')
                ->modalSubmitActionLabel('Load Attendance')
                ->modalCancelActionLabel('Cancel'),
        ];
    }

    public function loadAttendanceData(string $month, string $year): void
    {
        $this->selectedMonth = $month;
        $this->selectedYear = $year;

        // Reset to first page when loading new month/year
        if (!$this->showAttendanceTable) {
            $this->currentPage = 1;
        }

        // Get the first and last day of the selected month
        $startDate = Carbon::createFromFormat('Y-m-d', "{$year}-{$month}-01");
        $endDate = $startDate->copy()->endOfMonth();

        // Generate all dates for the month
        $this->dates = [];
        $currentDate = $startDate->copy();
        while ($currentDate->lte($endDate)) {
            $this->dates[] = $currentDate->format('Y-m-d');
            $currentDate->addDay();
        }

        // Get employees based on filter
        $employeesQuery = Usuario::with("tipo")->where('tipo_id', 1);

        // Apply employee filter if specified
        if (!empty($this->selectedEmployees)) {
            $employeesQuery->whereIn('id', $this->selectedEmployees);
        }

        $this->totalEmployees = $employeesQuery->count();
        $this->totalPages = ceil($this->totalEmployees / $this->perPage);

        $employees = $employeesQuery
            ->skip(($this->currentPage - 1) * $this->perPage)
            ->take($this->perPage)
            ->get();

        // Get attendance data for the month
        $attendanceQuery = Attendance::with(['usuario', 'tipo'])->where('tipo_id', 1)
            ->whereBetween('date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')]);

        // Filter attendance by selected employees if specified
        if (!empty($this->selectedEmployees)) {
            $attendanceQuery->whereIn('usuario_id', $this->selectedEmployees);
        }

        $attendanceRecords = $attendanceQuery->get();

        // Debug: Log counts for troubleshooting
        \Log::info("Monthly Attendance Debug", [
            'date_range' => [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')],
            'usuarios_count' => $employees->count(),
            'attendance_records_count' => $attendanceRecords->count(),
        ]);

        // Group attendance records by employee_id and date for easier lookup
        $attendanceByEmployeeAndDate = [];
        foreach ($attendanceRecords as $attendance) {
            $attendanceByEmployeeAndDate[$attendance->employee_id][$attendance->date->format('Y-m-d')] = $attendance;
        }

        // Build the attendance data array
        $this->attendanceData = [];
        foreach ($employees as $employee) {
            $employeeName = $employee->nombre;
            $this->attendanceData[$employee->id] = [
                'employee_mombre' => $employeeName,
                'attendance' => []
            ];

            foreach ($this->dates as $date) {
                $attendance = $attendanceByEmployeeAndDate[$employee->id][$date] ?? null;
                $this->attendanceData[$employee->id]['attendance'][$date] = $attendance
                    ? $attendance->attendanceType->code
                    : 'N/A';
            }
        }

        $this->showAttendanceTable = true;
    }

    public function nextPage(): void
    {
        if ($this->currentPage < $this->totalPages) {
            $this->currentPage++;
            $this->loadAttendanceData($this->selectedMonth, $this->selectedYear);
        }
    }

    public function previousPage(): void
    {
        if ($this->currentPage > 1) {
            $this->currentPage--;
            $this->loadAttendanceData($this->selectedMonth, $this->selectedYear);
        }
    }

    public function goToPage(int $page): void
    {
        if ($page >= 1 && $page <= $this->totalPages) {
            $this->currentPage = $page;
            $this->loadAttendanceData($this->selectedMonth, $this->selectedYear);
        }
    }

    public static function getNavigationLabel(): string
    {
        return 'Monthly Attendance View';
    }

    public static function shouldRegisterNavigation(array $parameters = []): bool
    {
        return true;
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Attendance Management';
    }

    /*public static function getNavigationIcon(): string
    {
        return 'heroicon-o-table-cells';
    }*/

    public static function getNavigationSort(): ?int
    {
        return 2;
    }
}
