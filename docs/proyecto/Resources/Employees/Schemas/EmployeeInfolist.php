<?php

namespace App\Filament\App\Resources\Employees\Schemas;

use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\Taxi\Attendance;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaTicket;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class EmployeeInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn ($record): string => 'Navegación rápida — ' . ($record->name ?? 'Empleado'))
                    ->description('Accede a las secciones del empleado y cuadrantes generales.')
                    ->columnSpanFull()
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('nav_grid')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.app.resources.employees.partials.employee-nav-grid'),
                    ]),

                Section::make('Resumen del mes actual')
                    ->columnSpanFull()
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('stats_summary')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.app.resources.employees.partials.employee-stats-summary'),
                    ]),

                Section::make('Datos del empleado')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('name')
                            ->label('Nombre'),
                        TextEntry::make('email')
                            ->label('Email'),
                        TextEntry::make('phone')
                            ->label('Teléfono'),
                        TextEntry::make('nif')
                            ->label('NIF'),
                        TextEntry::make('employee_code')
                            ->label('Código'),
                        TextEntry::make('bookingDepartment.name')
                            ->label('Departamento')
                            ->placeholder('Sin departamento'),
                        TextEntry::make('employment_started_at')
                            ->label('Alta')
                            ->date('d/m/Y')
                            ->placeholder('-'),
                    ]),

                Section::make('Contrato y Preferencias')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        TextEntry::make('contract_type')
                            ->label('Tipo de contrato')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'full_time'     => 'Jornada completa',
                                'part_time'     => 'Jornada parcial',
                                'rotating'      => 'Turnos rotativos',
                                'nights_only'   => 'Solo noche',
                                'mornings_only' => 'Solo mañana',
                                default         => $state ?? '—',
                            })
                            ->placeholder('—'),

                        TextEntry::make('shift_preference')
                            ->label('Preferencia de turno')
                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                'M'   => 'Mañana',
                                'T'   => 'Tarde',
                                'N'   => 'Noche',
                                'any' => 'Cualquiera',
                                default => $state ?? '—',
                            })
                            ->placeholder('—'),

                        TextEntry::make('max_weekends_per_month')
                            ->label('Máx. fines de semana/mes')
                            ->placeholder('Sin límite'),

                        TextEntry::make('employee_notes')
                            ->label('Notas internas')
                            ->columnSpanFull()
                            ->placeholder('—'),
                    ]),

                Section::make('Historial de turnos (últimos 3 meses)')
                    ->columnSpanFull()
                    ->collapsed()
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('shift_history')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.app.resources.employees.partials.employee-shift-history'),
                    ]),
            ]);
    }

    public static function buildStats(User $record): array
    {
        $empId = $record->id;
        $now   = now();
        $start = $now->copy()->startOfMonth()->startOfDay();
        $end   = $now->copy()->endOfMonth()->endOfDay();
        $month = (int) $now->format('m');
        $year  = (int) $now->format('Y');

        $daysInMonth = $start->daysInMonth;
        $workingDays = collect(range(1, $daysInMonth))
            ->filter(fn ($d) => ! Carbon::createFromDate($year, $month, $d)->isWeekend())
            ->count();

        $attendances = Attendance::where('usuario_id', $empId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $workedDays = $attendances->filter(fn ($a) => in_array($a->status, ['presente', 'tarde', 'temprano', 'completo']))->count();
        $absentDays = $attendances->filter(fn ($a) => $a->status === 'ausente')->count();
        $lateDays   = $attendances->filter(fn ($a) => $a->status === 'tarde')->count();

        $shifts = EmployeeShift::where('employee_id', $empId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get();

        $workingCodes   = [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE];
        $plannedShifts  = $shifts->filter(fn ($s) => in_array($s->shift_code, $workingCodes))->count();
        $morningShifts  = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_MANANA)->count();
        $afternoonShifts = $shifts->filter(fn ($s) => in_array($s->shift_code, [EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO]))->count();
        $nightShifts    = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_NOCHE)->count();
        $freeDays       = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE)->count();
        $freeDaysRequested = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_REQUESTED)->count();
        $freeDaysAutoNight = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_AUTO_NIGHT)->count();
        $vacationShifts = $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_VACACIONES)->count();
        $weekendShifts  = $shifts->filter(fn ($s) => in_array($s->shift_code, $workingCodes) && $s->date && $s->date->isWeekend())->count();

        $timeOffs = EmployeeTimeOff::where('employee_id', $empId)
            ->where(fn ($q) => $q
                ->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhereBetween('end_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(fn ($q2) => $q2->where('start_date', '<=', $start->toDateString())->where('end_date', '>=', $end->toDateString()))
            )->get();

        $timeOffDays = 0;
        foreach ($timeOffs->filter(fn ($t) => $t->status === EmployeeTimeOff::STATUS_APPROVED) as $to) {
            $s = $to->start_date->max($start->toDateString());
            $e = $to->end_date->min($end->toDateString());
            $timeOffDays += Carbon::parse($s)->diffInDays(Carbon::parse($e)) + 1;
        }
        $pendingTimeOff = $timeOffs->filter(fn ($t) => $t->status === EmployeeTimeOff::STATUS_PENDING)->count();

        $weeksInMonth   = max(1, ceil($daysInMonth / 7));
        $avgWorkedWeek  = round($workedDays / $weeksInMonth, 1);
        $avgPlannedWeek = round($plannedShifts / $weeksInMonth, 1);

        $totalAppts  = TaxistaAppointment::where('taxista_user_id', $empId)->count();
        $nextAppt    = TaxistaAppointment::where('taxista_user_id', $empId)
            ->where('appointment_date', '>=', $now->toDateString())
            ->where('status', '!=', 'cancelada')
            ->orderBy('appointment_date')->first();

        $openTickets  = TaxistaTicket::where('user_id', $empId)->whereNull('closed_at')->count();
        $totalTickets = TaxistaTicket::where('user_id', $empId)->count();

        $totalDocuments = TaxistaDocument::where('taxista_user_id', $empId)->count();

        return [
            'month_label'           => $now->translatedFormat('F Y'),
            'days_in_month'         => $daysInMonth,
            'working_days'          => $workingDays,
            'worked_days'           => $workedDays,
            'absent_days'           => $absentDays,
            'late_days'             => $lateDays,
            'planned_shifts'        => $plannedShifts,
            'morning_shifts'        => $morningShifts,
            'afternoon_shifts'      => $afternoonShifts,
            'night_shifts'          => $nightShifts,
            'weekend_shifts'        => $weekendShifts,
            'free_days'             => $freeDays,
            'free_days_requested'   => $freeDaysRequested,
            'free_days_auto_night'  => $freeDaysAutoNight,
            'vacation_shifts'       => $vacationShifts,
            'timeoff_days'          => $timeOffDays,
            'pending_timeoff'       => $pendingTimeOff,
            'avg_worked_week'       => $avgWorkedWeek,
            'avg_planned_week'      => $avgPlannedWeek,
            'total_appts'           => $totalAppts,
            'next_appt_date'        => $nextAppt?->appointment_date,
            'next_appt_time'        => $nextAppt?->appointment_time,
            'open_tickets'          => $openTickets,
            'total_tickets'         => $totalTickets,
            'total_documents'       => $totalDocuments,
        ];
    }

    /**
     * Build historical shift stats for the last 3 months.
     *
     * @return array<string, mixed>
     */
    public static function buildShiftHistory(User $record): array
    {
        $empId = $record->id;
        $workingCodes = [EmployeeShift::SHIFT_MANANA, EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO, EmployeeShift::SHIFT_NOCHE];

        $months = [];
        for ($i = 2; $i >= 0; $i--) {
            $date  = now()->subMonths($i);
            $start = $date->copy()->startOfMonth()->toDateString();
            $end   = $date->copy()->endOfMonth()->toDateString();

            $shifts = EmployeeShift::where('employee_id', $empId)
                ->whereBetween('date', [$start, $end])
                ->get();

            $months[] = [
                'label'            => $date->translatedFormat('F Y'),
                'total_working'    => $shifts->filter(fn ($s) => in_array($s->shift_code, $workingCodes))->count(),
                'morning'          => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_MANANA)->count(),
                'afternoon'        => $shifts->filter(fn ($s) => in_array($s->shift_code, [EmployeeShift::SHIFT_TARDE, EmployeeShift::SHIFT_PARTIDO]))->count(),
                'night'            => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_NOCHE)->count(),
                'weekend'          => $shifts->filter(fn ($s) => in_array($s->shift_code, $workingCodes) && $s->date?->isWeekend())->count(),
                'free'             => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE)->count(),
                'free_requested'   => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_REQUESTED)->count(),
                'free_auto_night'  => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_LIBRE && $s->libre_reason === EmployeeShift::LIBRE_REASON_AUTO_NIGHT)->count(),
                'vacation'         => $shifts->filter(fn ($s) => $s->shift_code === EmployeeShift::SHIFT_VACACIONES)->count(),
            ];
        }

        return ['months' => $months];
    }
}
