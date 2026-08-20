<?php

namespace App\Filament\App\Resources\Taxistas\RelationManagers;

use App\Models\BookingCalendar;
use App\Models\BookingDepartment;
use App\Models\BookingDepartmentSchedule;
use App\Models\TaxistaAppointment;
use Carbon\Carbon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AppointmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'appointments';

    protected static ?string $title = 'Citas';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('editing_record_id')
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                        $set('editing_record_id', $record?->getKey());
                    }),

                Hidden::make('taxista_user_id')
                    ->default(fn (): int => (int) $this->getOwnerRecord()->id)
                    ->required(),

                Hidden::make('created_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make('Cita previa')
                    ->schema([
                        TextInput::make('title')
                            ->label('Motivo')
                            ->required()
                            ->maxLength(255),

                        Select::make('booking_department_id')
                            ->label('Departamento')
                            ->relationship(
                                'department',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query
                                    ->meetingBookable()
                                    ->orderBy('name')
                            )
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText('Solo se muestran departamentos activos con servicio de citas y horario configurado.')
                            ->afterStateUpdated(function (Set $set): void {
                                $set('booking_calendar_id', null);
                            }),

                        Select::make('booking_calendar_id')
                            ->label('Calendario')
                            ->options(fn (Get $get): array => BookingCalendar::query()
                                ->active()
                                ->forDepartment($get('booking_department_id') ? (int) $get('booking_department_id') : null)
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->searchable()
                            ->preload()
                            ->helperText('Opcional. Si no se selecciona, la cita queda asociada solo al departamento.'),

                        Placeholder::make('department_schedule_summary')
                            ->label('Horario del departamento')
                            ->content(fn (Get $get): string => $this->getDepartmentScheduleSummary((int) $get('booking_department_id')))
                            ->visible(fn (Get $get): bool => filled($get('booking_department_id')))
                            ->columnSpanFull(),

                        DateTimePicker::make('starts_at')
                            ->label('Inicio')
                            ->seconds(false)
                            ->required()
                            ->live()
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    if (blank($value)) {
                                        return;
                                    }

                                    $departmentId = (int) $get('booking_department_id');
                                    if ($departmentId < 1) {
                                        $fail('Debe seleccionar un departamento.');

                                        return;
                                    }

                                    $startsAt = Carbon::parse((string) $value);
                                    $endsAtRaw = $get('ends_at');
                                    if (blank($endsAtRaw)) {
                                        return;
                                    }

                                    $endsAt = Carbon::parse((string) $endsAtRaw);

                                    if ($endsAt->lte($startsAt)) {
                                        $fail('La fecha final debe ser posterior a la fecha de inicio.');

                                        return;
                                    }

                                    if (! TaxistaAppointment::isWithinDepartmentSchedule($departmentId, $startsAt, $endsAt)) {
                                        $fail('La cita esta fuera del horario habilitado del departamento.');

                                        return;
                                    }

                                    $ignoreId = $get('editing_record_id') ? (int) $get('editing_record_id') : null;

                                    if (TaxistaAppointment::hasConflict(
                                        taxistaUserId: (int) $get('taxista_user_id'),
                                        departmentId: $departmentId,
                                        startsAt: $startsAt,
                                        endsAt: $endsAt,
                                        ignoreAppointmentId: $ignoreId
                                    )) {
                                        $fail('Existe otra cita solapada para este taxista en el departamento.');
                                    }
                                };
                            }),

                        DateTimePicker::make('ends_at')
                            ->label('Fin')
                            ->seconds(false)
                            ->required()
                            ->rule(function (Get $get) {
                                return function (string $attribute, mixed $value, \Closure $fail) use ($get): void {
                                    if (blank($value) || blank($get('starts_at'))) {
                                        return;
                                    }

                                    $startsAt = Carbon::parse((string) $get('starts_at'));
                                    $endsAt = Carbon::parse((string) $value);

                                    if ($endsAt->lte($startsAt)) {
                                        $fail('La fecha final debe ser posterior a la fecha de inicio.');
                                    }
                                };
                            }),

                        Select::make('status')
                            ->label('Estado')
                            ->required()
                            ->default('pendiente')
                            ->options([
                                'pendiente' => 'Pendiente',
                                'confirmada' => 'Confirmada',
                                'finalizada' => 'Finalizada',
                                'cancelada' => 'Cancelada',
                            ]),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Motivo')
                    ->searchable(),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge()
                    ->searchable(),

                TextColumn::make('calendar.name')
                    ->label('Calendario')
                    ->toggleable(),

                TextColumn::make('starts_at')
                    ->label('Inicio')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('ends_at')
                    ->label('Fin')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pendiente' => 'warning',
                        'confirmada' => 'info',
                        'finalizada' => 'success',
                        'cancelada' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'confirmada' => 'Confirmada',
                        'finalizada' => 'Finalizada',
                        'cancelada' => 'Cancelada',
                    ]),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva cita')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['taxista_user_id'] = (int) $this->getOwnerRecord()->id;
                        $data['created_by_user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private function getDepartmentScheduleSummary(int $departmentId): string
    {
        if ($departmentId < 1) {
            return 'Seleccione un departamento para ver disponibilidad.';
        }

        $department = BookingDepartment::query()->find($departmentId);
        if (! $department) {
            return 'Departamento no encontrado.';
        }

        $schedules = BookingDepartmentSchedule::query()
            ->where('booking_department_id', $departmentId)
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get(['day_of_week', 'start_time', 'end_time']);

        if ($schedules->isEmpty()) {
            return 'Sin horarios activos para cita previa.';
        }

        $ranges = $schedules
            ->groupBy('day_of_week')
            ->map(function ($rows, int $day): string {
                $slots = $rows
                    ->map(fn ($row): string => substr((string) $row->start_time, 0, 5).'-'.substr((string) $row->end_time, 0, 5))
                    ->implode(', ');

                return $this->dayName($day).': '.$slots;
            })
            ->implode(' | ');

        $duration = (int) ($department->meeting_duration ?: 30);

        return 'Duracion cita: '.$duration.' min. '.$ranges;
    }

    private function dayName(int $day): string
    {
        return match ($day) {
            1 => 'Lunes',
            2 => 'Martes',
            3 => 'Miercoles',
            4 => 'Jueves',
            5 => 'Viernes',
            6 => 'Sabado',
            7 => 'Domingo',
            default => 'Dia '.$day,
        };
    }
}
