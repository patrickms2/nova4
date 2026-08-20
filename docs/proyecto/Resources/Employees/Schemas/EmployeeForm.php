<?php

namespace App\Filament\App\Resources\Employees\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class EmployeeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Empleado')
                    ->columns(2)
                    ->schema([
                        Toggle::make('status')
                            ->label('Activo')
                            ->required()
                            ->default(true),

                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->string()
                            ->maxLength(255),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),

                        TextInput::make('phone')
                            ->label('Teléfono')
                            ->maxLength(50),

                        TextInput::make('nif')
                            ->label('NIF')
                            ->maxLength(50),

                        TextInput::make('employee_code')
                            ->label('Código empleado')
                            ->maxLength(50),

                        DatePicker::make('employment_started_at')
                            ->label('Alta')
                            ->native(false),

                        Select::make('booking_department_id')
                            ->label('Departamento')
                            ->relationship(
                                'bookingDepartment',
                                'name',
                                modifyQueryUsing: fn (Builder $query): Builder => $query->where('is_active', true)->orderBy('name'),
                            )
                            ->searchable()
                            ->preload(),
                    ]),

                Section::make('Contrato y Preferencias de Turno')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Select::make('contract_type')
                            ->label('Tipo de contrato')
                            ->options([
                                'full_time'    => 'Jornada completa',
                                'part_time'    => 'Jornada parcial',
                                'rotating'     => 'Turnos rotativos',
                                'nights_only'  => 'Solo noche',
                                'mornings_only' => 'Solo mañana',
                            ])
                            ->placeholder('Sin especificar')
                            ->nullable(),

                        Select::make('shift_preference')
                            ->label('Preferencia de turno')
                            ->options([
                                'M'   => 'Mañana',
                                'T'   => 'Tarde',
                                'N'   => 'Noche',
                                'any' => 'Cualquiera',
                            ])
                            ->placeholder('Sin preferencia')
                            ->nullable(),

                        TextInput::make('max_weekends_per_month')
                            ->label('Máx. fines de semana/mes')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->nullable()
                            ->placeholder('Sin límite'),

                        Textarea::make('employee_notes')
                            ->label('Notas internas (RRHH)')
                            ->rows(3)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),
            ]);
    }
}
