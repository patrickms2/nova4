<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Schemas;

use App\Filament\Components\Forms\Fields\CalendarInput;
use App\Models\BookingDepartment;
use App\Models\Taxi\TipoCitas;
use App\Models\TaxistaAppointment;
use App\Services\SlotService;
use App\Support\PortalTaxistaContext;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class TaxistaAppointmentForm2
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('created_by_user_id')
                    ->default(fn(): ?int => auth()->id()),

                Hidden::make('disabled_slots')
                    ->dehydrated(false)
                    ->default([]),

                Hidden::make('appointment_date')
                    ->dehydrated(false),

                Hidden::make('editing_record_id')
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                        $set('editing_record_id', $record?->getKey());
                    }),

                Hidden::make('availability_conflicts')
                    ->dehydrated(false),

                Hidden::make('ends_at')
                    ->required(),

                Wizard::make([
                    Step::make('STEP 1')
                        ->label('Motivo y departamento')
                        ->schema([
                            Select::make('taxista_user_id')
                                ->label('Taxista')
                                ->relationship(
                                    'taxista',
                                    'name',
                                    modifyQueryUsing: fn(Builder $query): Builder => PortalTaxistaContext::scopeTaxistaOptions($query)
                                )
                                ->default(fn(): ?int => PortalTaxistaContext::taxistaUserId())
                                ->searchable()
                                ->preload()
                                ->required()
                                ->hidden(fn(): bool => PortalTaxistaContext::isPortalPanel() || filled(PortalTaxistaContext::taxistaUserId())),

                            TextInput::make('title')
                                ->label('Motivo de cita')
                                ->required()
                                ->maxLength(255),

                            Select::make('tipo_cita_id')
                                ->label('Tipo de cita')
                                ->relationship('tipo', 'nombre')
                                ->searchable()
                                ->preload()
                                ->required(),

                            Select::make('booking_department_id')
                                ->label('Departamento')
                                ->relationship(
                                    'booking_department',
                                    'name',
                                    modifyQueryUsing: fn(Builder $query): Builder => $query->meetingBookable()->orderBy('name')
                                )
                                ->searchable()
                                ->preload()
                                ->required()
                                ->live()
                                ->helperText('Departamentos con servicio de citas y horarios disponibles.')
                                ->rule(function () {
                                    return function (string $attribute, mixed $value, \Closure $fail): void {
                                        if (blank($value)) {
                                            return;
                                        }

                                        $isBookable = BookingDepartment::query()
                                            ->meetingBookable()
                                            ->whereKey((int)$value)
                                            ->exists();

                                        if (!$isBookable) {
                                            $fail('El departamento seleccionado no acepta citas actualmente.');
                                        }
                                    };
                                })
                                ->afterStateUpdated(function (Get $get, Set $set, mixed $state, mixed $old): void {
                                    if (filled($get('editing_record_id')) && ($old === null || (string)$state === (string)$old)) {
                                        return;
                                    }

                                    $set('appointment_date', null);
                                    $set('starts_at', null);
                                    $set('ends_at', null);
                                    $set('disabled_slots', []);
                                    $set('availability_conflicts', null);
                                }),
                        ]),

                    Step::make('STEP 2')
                        ->label('Calendario y hora')
                        ->schema([
                            Placeholder::make('department_schedule_summary')
                                ->label('Horario del departamento')
                                ->content(function (Get $get): string {
                                    $departmentId = (int)$get('booking_department_id');
                                    if ($departmentId < 1) {
                                        return 'Seleccione un departamento para ver su horario.';
                                    }

                                    $duration = (int)BookingDepartment::query()->whereKey($departmentId)->value('meeting_duration');
                                    $duration = $duration > 0 ? $duration : 30;

                                    return 'Duracion de cita: ' . $duration . ' min.';
                                })
                                ->visible(fn(Get $get): bool => filled($get('booking_department_id')))
                                ->columnSpanFull(),

                            Grid::make(3)
                                ->schema([
                                    CalendarInput::make('appointment_date')
                                        ->label('Calendario')
                                        ->calendarLocale('es')
                                        ->minDate(today())
                                        ->dehydrated(false)
                                        ->required()
                                        ->live()
                                        ->visible(fn(Get $get): bool => filled($get('booking_department_id')))
                                        ->extraAttributes(fn(Get $get): array => [
                                            'wire:key' => 'taxista-appointment-calendar-' . ((string)($get('booking_department_id') ?: 'none')),
                                        ])
                                        ->disabledDates(function (Get $get): array {
                                            $departmentId = (int)$get('booking_department_id');
                                            if ($departmentId < 1) {
                                                return [];
                                            }

                                            return SlotService::getDisabledDatesForDepartment(
                                                $departmentId,
                                                now()->startOfDay(),
                                                now()->addDays(45)->endOfDay(),
                                            );
                                        })
                                        ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                                            if (!$record?->starts_at) {
                                                return;
                                            }

                                            $set('appointment_date', $record->starts_at->toDateString());
                                            $set('starts_at', $record->starts_at->format('Y-m-d H:i:s'));
                                            $set('ends_at', $record->ends_at?->format('Y-m-d H:i:s'));
                                        })
                                        ->afterStateUpdated(function (Get $get, Set $set, mixed $state, ?TaxistaAppointment $record): void {
                                            if (blank($state)) {
                                                $set('starts_at', null);
                                                $set('ends_at', null);
                                                $set('disabled_slots', []);
                                                $set('availability_conflicts', null);

                                                return;
                                            }

                                            $departmentId = (int)$get('booking_department_id');
                                            $date = Carbon::parse((string)$state);

                                            $slots = SlotService::buildSlotsForDepartmentDate(
                                                bookingDepartmentId: $departmentId,
                                                date: $date,
                                                ignoreAppointmentId: $record?->getKey(),
                                            );

                                            $set('disabled_slots', array_keys(array_filter($slots['disabled'])));
                                            $set('availability_conflicts', null);

                                            if ($get('starts_at') && !array_key_exists((string)$get('starts_at'), $slots['options'])) {
                                                $set('starts_at', null);
                                                $set('ends_at', null);
                                            }
                                        })
                                        ->columnSpan(2),

                                    ToggleButtons::make('starts_at')
                                        ->label('Horas disponibles')
                                        ->required()
                                        ->inline(false)
                                        ->visible(fn(Get $get): bool => filled($get('booking_department_id')) && filled($get('appointment_date')))
                                        ->options(function (Get $get, ?TaxistaAppointment $record): array {
                                            $departmentId = (int)$get('booking_department_id');
                                            $appointmentDate = $get('appointment_date');

                                            if ($departmentId < 1 || blank($appointmentDate)) {
                                                return [];
                                            }

                                            return SlotService::buildSlotsForDepartmentDate(
                                                bookingDepartmentId: $departmentId,
                                                date: Carbon::parse((string)$appointmentDate),
                                                ignoreAppointmentId: $record?->getKey(),
                                            )['options'];
                                        })
                                        ->disableOptionWhen(fn(string $value, Get $get): bool => in_array($value, (array)$get('disabled_slots'), true))
                                        ->afterStateUpdated(function (Get $get, Set $set, mixed $state, ?TaxistaAppointment $record): void {
                                            if (blank($state)) {
                                                $set('ends_at', null);

                                                return;
                                            }

                                            $departmentId = (int)$get('booking_department_id');
                                            $selectedStart = Carbon::parse((string)$state);

                                            $slots = SlotService::buildSlotsForDepartmentDate(
                                                bookingDepartmentId: $departmentId,
                                                date: $selectedStart->copy()->startOfDay(),
                                                ignoreAppointmentId: $record?->getKey(),
                                            );

                                            $endsAt = $selectedStart->copy()->addMinutes((int)$slots['duration']);
                                            $set('ends_at', $endsAt->format('Y-m-d H:i:s'));
                                        })
                                        ->columnSpan(1),
                                ])
                                ->columnSpanFull(),
                        ]),

                    Step::make('STEP 3')
                        ->label('Confirmación')
                        ->columns(2)
                        ->schema([
                            Placeholder::make('summary_fecha')
                                ->label('Día')
                                ->content(fn(Get $get): string => filled($get('appointment_date'))
                                    ? Carbon::parse((string)$get('appointment_date'))->format('d/m/Y')
                                    : '-'),

                            Placeholder::make('summary_hora')
                                ->label('Hora')
                                ->content(fn(Get $get): string => filled($get('starts_at'))
                                    ? Carbon::parse((string)$get('starts_at'))->format('H:i')
                                    : '-'),

                            Placeholder::make('summary_motivo')
                                ->label('Motivo')
                                ->content(fn(Get $get): string => (string)($get('title') ?: '-')),

                            Placeholder::make('summary_tipo')
                                ->label('Tipo de cita')
                                ->content(function (Get $get): string {
                                    $tipoId = (int)$get('tipo_cita_id');
                                    if ($tipoId < 1) {
                                        return '-';
                                    }

                                    return (string)(TipoCitas::query()->whereKey($tipoId)->value('nombre') ?: '-');
                                }),

                            Placeholder::make('summary_departamento')
                                ->label('Departamento')
                                ->content(function (Get $get): string {
                                    $departmentId = (int)$get('booking_department_id');
                                    if ($departmentId < 1) {
                                        return '-';
                                    }

                                    return (string)(BookingDepartment::query()->whereKey($departmentId)->value('name') ?: '-');
                                }),

                            Select::make('status')
                                ->label('Estado')
                                ->required()
                                ->options([
                                    'pendiente' => 'Pendiente',
                                    'confirmada' => 'Confirmada',
                                    'finalizada' => 'Finalizada',
                                    'cancelada' => 'Cancelada',
                                ])
                                ->default('pendiente'),

                            Textarea::make('notes')
                                ->label('Descripción / nota')
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ]);
    }
}
