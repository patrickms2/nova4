<?php

namespace App\Filament\App\Resources\BookingDepartments\Schemas;

use App\Models\User;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class BookingDepartmentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Departamento')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),

                        ColorPicker::make('color')
                            ->label('Color')
                            ->default('#ef4444')
                            ->required(),

                        Select::make('meeting_duration')
                            ->label('Duracion cita')
                            ->required()
                            ->options([
                                15 => '15 min',
                                30 => '30 min',
                                45 => '45 min',
                                60 => '60 min',
                            ])
                            ->default(30),

                        Select::make('created_by')
                            ->label('Encargado')
                            ->options(fn(): array => User::query()
                                ->whereIn('role', ['admin', 'booking', 'encargado', 'empleado'])
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray()
                            )
                            ->default(fn(): ?int => auth()->id())
                            ->searchable()
                            ->preload(),

                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),

                        Toggle::make('has_meetings_service')
                            ->label('Servicio de citas')
                            ->helperText('Activa para que este departamento gestione citas.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),

                        Toggle::make('has_documents_service')
                            ->label('Servicio de documentos')
                            ->helperText('Activa para que este departamento gestione documentos.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),

                        Toggle::make('has_taxistas_service')
                            ->label('Servicio de taxistas')
                            ->helperText('Activa para que este departamento gestione taxistas.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),
                        Toggle::make('has_servicios_service')
                            ->label('Servicio de servicios')
                            ->helperText('Activa para que este departamento gestione servicios.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),
                        Toggle::make('has_empleados_service')
                            ->label('Servicio de empleados')
                            ->helperText('Activa para que este departamento gestione empleados.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),
                        Toggle::make('has_hoteles_service')
                            ->label('Servicio de hoteles')
                            ->helperText('Activa para que este departamento gestione hoteles.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),
                        Toggle::make('has_tickets_service')
                            ->label('Servicio de tickets')
                            ->helperText('Activa para que este departamento gestione tickets de soporte.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),

                        Toggle::make('has_chats_service')
                            ->label('Servicio de chat')
                            ->helperText('Activa para que este departamento tenga chat interno.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(true),

                        Toggle::make('has_shifts_service')
                            ->label('Servicio de turnos')
                            ->helperText('Activa para gestionar turnos y vacaciones de empleados.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(false),

                        Toggle::make('has_attendance_service')
                            ->label('Registro de asistencia')
                            ->helperText('Activa para registrar entradas y salidas de empleados.')
                            ->disabled(fn(): bool => !(auth()->user()?->isAdmin() ?? false))
                            ->default(false),

                        CheckboxList::make('operational_days')
                            ->label('Días operativos')
                            ->options([
                                1 => 'Lunes',
                                2 => 'Martes',
                                3 => 'Miércoles',
                                4 => 'Jueves',
                                5 => 'Viernes',
                                6 => 'Sábado',
                                7 => 'Domingo',
                            ])
                            ->columns(4)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Horario cita previa')
                    ->description('Este horario define los slots disponibles para que el taxista reserve cita.')
                    ->schema([
                        Repeater::make('schedules')
                            ->label('Horarios')
                            ->relationship('schedules')
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(4)
                            ->schema([
                                Select::make('day_of_week')
                                    ->label('Dia')
                                    ->required()
                                    ->options([
                                        1 => 'Lunes',
                                        2 => 'Martes',
                                        3 => 'Miercoles',
                                        4 => 'Jueves',
                                        5 => 'Viernes',
                                        6 => 'Sabado',
                                        7 => 'Domingo',
                                    ]),
                                TimePicker::make('start_time')
                                    ->label('Inicio')
                                    ->seconds(false)
                                    ->required(),
                                TimePicker::make('end_time')
                                    ->label('Fin')
                                    ->seconds(false)
                                    ->required(),
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ]),
                    ]),

                Section::make('Turnos del departamento')
                    ->description('Configura los turnos de trabajo (Mañana, Partido, Noche) con sus horarios y días.')
                    ->schema([
                        Repeater::make('shiftSchedules')
                            ->label('Turnos')
                            ->relationship('shiftSchedules')
                            ->defaultItems(0)
                            ->reorderable(false)
                            ->columns(4)
                            ->schema([
                                Select::make('shift_code')
                                    ->label('Turno')
                                    ->required()
                                    ->options([
                                        'M' => 'Mañana',
                                        'P' => 'Partido',
                                        'N' => 'Noche',
                                    ]),
                                TextInput::make('label')
                                    ->label('Etiqueta')
                                    ->maxLength(255)
                                    ->placeholder('Ej: Mañana'),
                                TimePicker::make('start_time')
                                    ->label('Inicio')
                                    ->seconds(false)
                                    ->required(),
                                TimePicker::make('end_time')
                                    ->label('Fin')
                                    ->seconds(false)
                                    ->required(),
                                CheckboxList::make('days_of_week')
                                    ->label('Días')
                                    ->options([
                                        1 => 'L',
                                        2 => 'M',
                                        3 => 'X',
                                        4 => 'J',
                                        5 => 'V',
                                        6 => 'S',
                                        7 => 'D',
                                    ])
                                    ->columns(7)
                                    ->required()
                                    ->columnSpanFull(),
                                TextInput::make('min_employees')
                                    ->label('Mín. empleados')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                                TextInput::make('max_employees')
                                    ->label('Máx. empleados')
                                    ->numeric()
                                    ->default(1)
                                    ->minValue(1),
                                Toggle::make('is_active')
                                    ->label('Activo')
                                    ->default(true),
                            ]),
                    ]),
            ]);
    }
}
