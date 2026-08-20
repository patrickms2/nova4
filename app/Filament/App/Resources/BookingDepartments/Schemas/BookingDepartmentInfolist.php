<?php

namespace App\Filament\App\Resources\BookingDepartments\Schemas;

use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use Filament\Infolists\Components\ColorEntry;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingDepartmentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Departamento')
                    ->schema([
                        TextEntry::make('name')->label('Nombre'),
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('creator.name')->label('Encargado')->placeholder('-'),
                        TextEntry::make('meeting_duration')
                            ->label('Duracion cita')
                            ->formatStateUsing(fn (?int $state): string => ($state ?: 30) . ' min'),
                        ColorEntry::make('color')->label('Color'),
                        IconEntry::make('is_active')->label('Activo')->boolean(),
                    ])
                    ->columns(2),

                Section::make('Servicios activados')
                    ->schema([
                        IconEntry::make('has_meetings_service')->label('Citas')->boolean(),
                        IconEntry::make('has_documents_service')->label('Documentos')->boolean(),
                        IconEntry::make('has_tickets_service')->label('Tickets')->boolean(),
                        IconEntry::make('has_chats_service')->label('Chat')->boolean(),
                        IconEntry::make('has_shifts_service')->label('Turnos')->boolean(),
                        IconEntry::make('has_attendance_service')->label('Registro Asist.')->boolean(),
                    ])
                    ->columns(3),

                Section::make('Estadísticas del departamento')
                    ->schema([
                        TextEntry::make('employees_count')
                            ->label('Nº empleados')
                            ->state(fn (BookingDepartment $record): int => $record->employees()->count()),

                        TextEntry::make('operational_days')
                            ->label('Días operativos')
                            ->formatStateUsing(fn ($state): string => self::formatOperationalDays($state)),

                        TextEntry::make('shift_schedules_summary')
                            ->label('Turnos configurados')
                            ->state(function (BookingDepartment $record): string {
                                $schedules = $record->shiftSchedules()->where('is_active', true)->get();

                                if ($schedules->isEmpty()) {
                                    return 'Sin turnos configurados';
                                }

                                return $schedules->map(function ($s): string {
                                    $days = implode(', ', $s->dayLabels());

                                    return $s->shiftLabel() . ' (' . substr((string) $s->start_time, 0, 5) . '–' . substr((string) $s->end_time, 0, 5) . ') [' . $days . '] ' . $s->min_employees . '–' . $s->max_employees . ' emp.';
                                })->implode(' | ');
                            })
                            ->columnSpanFull(),

                        TextEntry::make('monthly_coverage')
                            ->label('Cobertura mes actual')
                            ->state(function (BookingDepartment $record): string {
                                $monthStart = now()->startOfMonth()->toDateString();
                                $monthEnd = now()->endOfMonth()->toDateString();

                                $shifts = EmployeeShift::query()
                                    ->whereHas('employee', fn ($q) => $q->where('booking_department_id', $record->id))
                                    ->whereDate('date', '>=', $monthStart)
                                    ->whereDate('date', '<=', $monthEnd)
                                    ->whereIn('shift_code', ['M', 'P', 'N'])
                                    ->selectRaw('shift_code, COUNT(*) as cnt')
                                    ->groupBy('shift_code')
                                    ->pluck('cnt', 'shift_code');

                                $total = $shifts->sum();

                                if ($total === 0) {
                                    return 'Sin turnos asignados este mes';
                                }

                                $m = $shifts->get('M', 0);
                                $p = $shifts->get('P', 0);
                                $n = $shifts->get('N', 0);

                                return "Total: {$total} — M: {$m}, P: {$p}, N: {$n}";
                            })
                            ->columnSpanFull(),

                        TextEntry::make('appointments_count_stat')
                            ->label('Citas (mes)')
                            ->state(function (BookingDepartment $record): int {
                                return $record->appointments()
                                    ->whereDate('created_at', '>=', now()->startOfMonth())
                                    ->count();
                            }),

                        TextEntry::make('tickets_count_stat')
                            ->label('Tickets abiertos')
                            ->state(function (BookingDepartment $record): int {
                                return $record->tickets()
                                    ->whereIn('status', ['abierto', 'en_proceso'])
                                    ->count();
                            }),
                    ])
                    ->columns(2),

                Section::make('Turnos del departamento')
                    ->schema([
                        RepeatableEntry::make('shiftSchedules')
                            ->label('Configuración de turnos')
                            ->schema([
                                TextEntry::make('shift_code')
                                    ->label('Turno')
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'M' => 'Mañana',
                                        'P' => 'Partido',
                                        'N' => 'Noche',
                                        default => $state ?? '-',
                                    })
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'M' => 'warning',
                                        'P' => 'info',
                                        'N' => 'gray',
                                        default => 'secondary',
                                    }),
                                TextEntry::make('start_time')
                                    ->label('Inicio')
                                    ->time('H:i'),
                                TextEntry::make('end_time')
                                    ->label('Fin')
                                    ->time('H:i'),
                                TextEntry::make('days_of_week')
                                    ->label('Días')
                                    ->formatStateUsing(fn ($state): string => self::formatDaysShort($state)),
                                TextEntry::make('min_employees')
                                    ->label('Mín.'),
                                TextEntry::make('max_employees')
                                    ->label('Máx.'),
                                IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),
                            ])
                            ->columns(7)
                            ->contained(false),
                    ]),

                Section::make('Horas de citas')
                    ->schema([
                        RepeatableEntry::make('schedules')
                            ->label('Horarios configurados')
                            ->schema([
                                TextEntry::make('day_of_week')
                                    ->label('Dia')
                                    ->formatStateUsing(fn (?int $state): string => self::dayLabel($state)),
                                TextEntry::make('start_time')
                                    ->label('Inicio')
                                    ->time('H:i'),
                                TextEntry::make('end_time')
                                    ->label('Fin')
                                    ->time('H:i'),
                                IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),
                            ])
                            ->columns(4)
                            ->contained(false),
                    ]),
            ]);
    }

    private static function dayLabel(?int $dayOfWeek): string
    {
        return match ($dayOfWeek) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
            default => '-',
        };
    }

    /** @param array<int>|string|null $days */
    private static function formatOperationalDays(mixed $days): string
    {
        if (is_string($days)) {
            $days = json_decode($days, true);
        }

        if (! is_array($days) || empty($days)) {
            return 'No configurado';
        }

        $map = [1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'];

        return implode(', ', array_map(fn (int $d): string => $map[$d] ?? '?', $days));
    }

    /** @param array<int>|string|null $days */
    private static function formatDaysShort(mixed $days): string
    {
        if (is_string($days)) {
            $days = json_decode($days, true);
        }

        if (! is_array($days) || empty($days)) {
            return '-';
        }

        $map = [1 => 'L', 2 => 'M', 3 => 'X', 4 => 'J', 5 => 'V', 6 => 'S', 7 => 'D'];

        return implode(', ', array_map(fn (int $d): string => $map[$d] ?? '?', $days));
    }
}
