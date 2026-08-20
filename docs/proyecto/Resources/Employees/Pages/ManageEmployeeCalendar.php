<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\Taxi\Attendance;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaTicket;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Pages\Page;
use Filament\Support\Enums\Width;
use Illuminate\Support\Carbon;

class ManageEmployeeCalendar extends Page
{
    protected static string $resource = EmployeeResource::class;

    public User $record;

    protected static ?string $title = 'Calendario';

    protected static ?string $navigationLabel = 'Calendario';

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.app.resources.employees.pages.employee-calendario';

    public int $month;

    public int $year;

    public array $calendarDays = [];

    public array $stats = [];

    public array $monthNames = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

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
        $empId = $this->record->id;
        $start = Carbon::createFromDate($this->year, $this->month, 1)->startOfDay();
        $end   = $start->copy()->endOfMonth()->endOfDay();

        $shifts = EmployeeShift::where('employee_id', $empId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn ($s) => $s->date->format('Y-m-d'));

        $timeOffs = EmployeeTimeOff::where('employee_id', $empId)
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start->toDateString())->where('end_date', '>=', $end->toDateString()))
            )->get();

        $attendances = Attendance::where('usuario_id', $empId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn ($a) => $a->date->format('Y-m-d'));

        $appointments = TaxistaAppointment::where('taxista_user_id', $empId)
            ->whereBetween('appointment_date', [$start->toDateString(), $end->toDateString()])
            ->get()->groupBy(fn ($a) => Carbon::parse($a->appointment_date)->format('Y-m-d'));

        $tickets = TaxistaTicket::where('user_id', $empId)
            ->whereBetween('created_at', [$start, $end])
            ->get()->groupBy(fn ($t) => $t->created_at->format('Y-m-d'));

        $swapRequests = ShiftSwapRequest::where('requester_user_id', $empId)
            ->where('status', ShiftSwapRequest::STATUS_PENDING)
            ->whereBetween('swap_date', [$start->toDateString(), $end->toDateString()])
            ->get()->keyBy(fn ($r) => $r->swap_date->format('Y-m-d'));

        $daysInMonth  = $start->daysInMonth;
        $firstDow     = $start->dayOfWeek;
        $days         = [];

        for ($d = 1; $d <= $daysInMonth; $d++) {
            $date    = Carbon::createFromDate($this->year, $this->month, $d);
            $dateStr = $date->format('Y-m-d');

            $shift    = $shifts->get($dateStr);
            $att      = $attendances->get($dateStr);
            $appts    = $appointments->get($dateStr, collect());
            $tix      = $tickets->get($dateStr, collect());
            $timeOff  = $timeOffs->first(fn ($to) => $to->start_date->format('Y-m-d') <= $dateStr && $to->end_date->format('Y-m-d') >= $dateStr);
            $swap     = $swapRequests->get($dateStr);

            $hasPending = false;
            $events = [];
            if ($shift) {
                $events[] = ['type' => 'shift', 'code' => $shift->shift_code, 'label' => $shift->shift_code];
            }
            if ($att) {
                $events[] = ['type' => 'attendance', 'status' => $att->status, 'label' => mb_substr($att->status ?? 'P', 0, 1)];
            }
            if ($timeOff) {
                $events[] = ['type' => 'timeoff', 'kind' => $timeOff->type, 'status' => $timeOff->status, 'label' => strtoupper(mb_substr($timeOff->type ?? 'V', 0, 1))];
                if ($timeOff->status === EmployeeTimeOff::STATUS_PENDING) {
                    $hasPending = true;
                }
            }
            if ($swap) {
                $events[] = ['type' => 'swap_request', 'swap_type' => $swap->type, 'status' => $swap->status, 'label' => $swap->type === 'dayoff' ? 'DL' : 'SW', 'notes' => $swap->requester_notes];
                $hasPending = true;
            }
            foreach ($appts as $appt) {
                $events[] = ['type' => 'appointment', 'status' => $appt->status, 'label' => 'C'];
            }
            foreach ($tix as $ticket) {
                $events[] = ['type' => 'ticket', 'status' => $ticket->status, 'label' => 'T'];
            }

            $days[] = [
                'day'        => $d,
                'date'       => $dateStr,
                'dow'        => $date->dayOfWeek,
                'isWeekend'  => $date->isWeekend(),
                'isToday'    => $date->isToday(),
                'hasPending' => $hasPending,
                'events'     => $events,
            ];
        }

        $this->calendarDays = $days;

        $this->buildStats($empId, $start, $end, $shifts, $attendances, $timeOffs, $appointments, $tickets);
    }

    private function buildStats(int $empId, Carbon $start, Carbon $end, $shifts, $attendances, $timeOffs, $appointments, $tickets): void
    {
        $daysInMonth   = $start->daysInMonth;
        $workingDays   = collect(range(1, $daysInMonth))->filter(fn ($d) => ! Carbon::createFromDate($this->year, $this->month, $d)->isWeekend())->count();

        $workedDays    = $attendances->filter(fn ($a) => in_array($a->status, ['presente', 'tarde', 'temprano', 'completo']))->count();
        $absentDays    = $attendances->filter(fn ($a) => $a->status === 'ausente')->count();
        $lateDays      = $attendances->filter(fn ($a) => $a->status === 'tarde')->count();

        $plannedShifts = $shifts->filter(fn ($s) => ! in_array($s->shift_code, [EmployeeShift::SHIFT_LIBRE, EmployeeShift::SHIFT_VACACIONES, EmployeeShift::SHIFT_BAJA]))->count();
        $freeDays      = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE)->count();

        $timeOffDays   = 0;
        foreach ($timeOffs->filter(fn ($t) => $t->status === EmployeeTimeOff::STATUS_APPROVED) as $to) {
            $s = $to->start_date->max($start->toDateString());
            $e = $to->end_date->min($end->toDateString());
            $timeOffDays += Carbon::parse($s)->diffInDays(Carbon::parse($e)) + 1;
        }
        $pendingTimeOff = $timeOffs->filter(fn ($t) => $t->status === EmployeeTimeOff::STATUS_PENDING)->count();

        $weeksInMonth   = ceil($daysInMonth / 7);
        $avgWorkedWeek  = $weeksInMonth > 0 ? round($workedDays / $weeksInMonth, 1) : 0;
        $avgPlannedWeek = $weeksInMonth > 0 ? round($plannedShifts / $weeksInMonth, 1) : 0;

        $totalAppts   = $appointments->flatten()->count();
        $nextAppt     = TaxistaAppointment::where('taxista_user_id', $empId)
            ->where('appointment_date', '>=', now()->toDateString())
            ->where('status', '!=', 'cancelada')
            ->orderBy('appointment_date')->first();

        $totalTickets    = $tickets->flatten()->count();
        $openTickets     = TaxistaTicket::where('user_id', $empId)
            ->whereNull('closed_at')->count();

        $totalDocuments = \App\Models\TaxistaDocument::where('taxista_user_id', $empId)->count();

        $totalShiftsMonth   = $shifts->count();
        $vacationShifts     = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_VACACIONES)->count();

        $this->stats = [
            'days_in_month'    => $daysInMonth,
            'working_days'     => $workingDays,
            'worked_days'      => $workedDays,
            'absent_days'      => $absentDays,
            'late_days'        => $lateDays,
            'planned_shifts'   => $plannedShifts,
            'free_days'        => $freeDays,
            'vacation_shifts'  => $vacationShifts,
            'timeoff_days'     => $timeOffDays,
            'pending_timeoff'  => $pendingTimeOff,
            'avg_worked_week'  => $avgWorkedWeek,
            'avg_planned_week' => $avgPlannedWeek,
            'total_appts'      => $totalAppts,
            'next_appt_date'   => $nextAppt?->appointment_date,
            'next_appt_time'   => $nextAppt?->appointment_time,
            'total_tickets'    => $totalTickets,
            'open_tickets'     => $openTickets,
            'total_documents'  => $totalDocuments,
            'total_shifts'     => $totalShiftsMonth,
        ];
    }
}
