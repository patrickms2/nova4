<?php

namespace App\Filament\App\Resources\TaxistaAppointments\Schemas;

use App\Filament\Components\Forms\Fields\CalendarInput;
use App\Models\BookingDepartment;
use App\Models\Taxi\TipoCitas;
use App\Models\TaxistaAppointment;
use App\Services\SlotService;
use App\Support\DepartmentManagerAccess;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Actions\ButtonAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class TaxistaAppointmentForm
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
                    Hidden::make('booking_department_id')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                            $set(
                                'booking_department_id',
                                $record?->booking_department_id
                                    ?? (PortalTaxistaContext::isPortalPanel() ? null : DepartmentManagerAccess::defaultDepartmentId('has_meetings_service'))
                            );
                        }),
                    Hidden::make('booking_department_label')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                            if (!$record) {
                                return;
                            }

                            $departmentName = $record->department?->name
                                ?? BookingDepartment::query()->whereKey((int)$record->booking_department_id)->value('name');

                            $set('booking_department_label', is_string($departmentName) ? $departmentName : null);
                        }),

                    Hidden::make('tipo_cita_id')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                            $set('tipo_cita_id', $record?->tipo_cita_id);
                        }),

                    Hidden::make('tipo_cita_label')
                        ->dehydrated(false)
                        ->afterStateHydrated(function (Set $set, ?TaxistaAppointment $record): void {
                            if (!$record) {
                                return;
                            }

                            $tipoLabel = TipoCitas::query()->whereKey((int)($record->tipo_cita_id ?? 0))->value('nombre');

                            $set('tipo_cita_label', is_string($tipoLabel) ? $tipoLabel : null);
                        }),

                    Hidden::make('status')
                        ->default(
                        fn(Get $get) => PortalTaxistaContext::isPortalPanel()? 'pendiente' : 'confirmada'),
                Grid::make()
                    ->columns([
                        'default' => 1,
                        'sm' => 4,
                        'md' => 4,
                        'lg' => 4,
                        'xl' => 4,
                    ])
                    ->columnSpanFull()
                    ->schema([
                        Section::make()
                            ->columnSpan([
                                'default' => 3,
                                'md' => 3,
                                'xl' => 3,
                            ])
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
                                    ->hidden(fn(): bool => PortalTaxistaContext::isPortalPanel()),

                                ToggleButtons::make('booking_department_id')
                                    ->label('Seleccione Departamento')
                                    ->options(fn(): array => PortalTaxistaContext::isPortalPanel()
                                        ? BookingDepartment::query()
                                            ->meetingBookable()
                                            ->orderBy('name')
                                            ->pluck('name', 'id')
                                            ->toArray()
                                        : DepartmentManagerAccess::scopeManagedServiceDepartments(
                                            BookingDepartment::query()->meetingBookable()->orderBy('name'),
                                            'has_meetings_service',
                                        )->pluck('name', 'id')->toArray())
                                    ->required()
                                    ->live()
                                    ->inline()
                                    ->dehydratedWhenHidden()
                                    ->visible(fn(Get $get): bool => !filled($get('booking_department_id')))
                                    ->helperText('Departamentos con servicio de citas y horarios disponibles.')
                                    ->rule(function () {
                                        return function (string $attribute, mixed $value, \Closure $fail): void {
                                            if (blank($value)) {
                                                return;
                                            }

                                            $isBookable = BookingDepartment::query()
                                                ->meetingBookable()
                                                ->when(! PortalTaxistaContext::isPortalPanel(), fn (Builder $query): Builder => DepartmentManagerAccess::scopeManagedServiceDepartments($query, 'has_meetings_service'))
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

                                        $departmentName = null;

                                        if (filled($state)) {
                                            $departmentName = BookingDepartment::query()->whereKey((int)$state)->value('name');
                                        }

                                        $set('booking_department_label', is_string($departmentName) ? $departmentName : null);
                                        $set('tipo_cita_id', null);
                                        $set('tipo_cita_label', null);
                                        $set('title', null);
                                    }),

                                ToggleButtons::make('tipo_cita_id')
                                    ->label('Tipo')
                                    ->inline()
                                    ->live()
                                    ->required()
                                    ->helperText('Especifique el tipo de cita')
                                    ->dehydratedWhenHidden()
                                    ->visible(
                                        fn(Get $get): bool => filled($get('booking_department_id')) && (blank($get('tipo_cita_id')))
                                    )
                                    ->options(fn(): array => TipoCitas::query()
                                        ->orderBy('orden')
                                        ->orderBy('nombre')
                                        ->pluck('nombre', 'id')
                                        ->toArray()
                                    )
                                    ->extraInputAttributes([
                                        'x-on:change' => "window.dispatchEvent(new CustomEvent('go-to-wizard-step', { detail: { key: 'taxista-appointment-wizard', step: 'appointment-step-2' } }))",
                                    ])
                                    ->afterStateUpdated(function (Set $set, mixed $state): void {
                                        $tipoLabel = null;

                                        if (filled($state)) {
                                            $tipoLabel = TipoCitas::query()->whereKey((int)$state)->value('nombre');
                                        }

                                        $set('tipo_cita_label', is_string($tipoLabel) ? $tipoLabel : null);
                                        $set('title', null);
                                    })
                                    ->label(fn(Get $get): string => (string)($get('tipo_cita_label') ?: 'Tipo')),

                                CalendarInput::make('appointment_date')
                                    ->label('Seleccione día en el Calendario')
                                    ->calendarLocale('es')
                                    ->minDate(today())
                                    ->dehydrated(false)
                                    ->required()
                                    ->live()
                                    ->dehydratedWhenHidden()
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) && filled($get('tipo_cita_id')) && (blank($get('appointment_date'))))
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
                                    ->live()
                                    ->inline(true)
                                    ->dehydratedWhenHidden()
                                    ->extraFieldWrapperAttributes([
                                        'class' => 'nova-slot-swipe-wrp',
                                    ])
                                    ->extraAttributes([
                                        'class' => 'nova-slot-swipe-track',
                                    ])
                                    ->colors([
                                        'primary' => 'primary',
                                    ])
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) && filled($get('appointment_date')) && (blank($get('starts_at'))))
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

                                        if (!TaxistaAppointment::isWithinDepartmentSchedule($departmentId, $selectedStart, $endsAt)) {
                                            throw ValidationException::withMessages([
                                                'starts_at' => 'El horario seleccionado no pertenece al horario habilitado del departamento.',
                                            ]);
                                        }

                                        if (TaxistaAppointment::hasDepartmentConflict($departmentId, $selectedStart, $endsAt, $record?->getKey())) {
                                            throw ValidationException::withMessages([
                                                'starts_at' => 'Ese horario ya fue reservado. Seleccione otro slot.',
                                            ]);
                                        }
                                    })
                                    ->columnSpan(1),
                                TextInput::make('title')
                                    ->label('Motivo')
                                    ->visible(fn(Get $get): bool => (filled($get('booking_department_id')) && filled($get('tipo_cita_id'))))
                                    ->nullable()
                                    ->maxLength(255)
                                    ->visible(false),

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
                                    ->visible(false)
                                    ->columnSpanFull(),

                                Grid::make()
                                    ->columns(['default' => 2])
                                    ->visible(fn(Get $get): bool => filled($get('booking_department_id')) && filled($get('tipo_cita_id')) && filled($get('appointment_date')) && filled($get('starts_at')))
                                    ->columnSpanFull()
                                    ->schema([
                                        Placeholder::make('summary_fecha')
                                            ->label('Dia')
                                            ->content(fn(Get $get): string => filled($get('appointment_date'))
                                                ? Carbon::parse((string)$get('appointment_date'))->format('d/m/Y')
                                                : '-'),

                                        Placeholder::make('summary_hora')
                                            ->label('Hora')
                                            ->content(fn(Get $get): string => filled($get('starts_at'))
                                                ? Carbon::parse((string)$get('starts_at'))->format('H:i')
                                                : '-'),

                                        Placeholder::make('summary_tipo')
                                            ->label('Tipo')
                                            ->content(fn(Get $get): string => (string)($get('tipo_cita_label') ?: '-')),

                                        Placeholder::make('summary_departamento')
                                            ->label('Departamento')
                                            ->content(function (Get $get): string {
                                                $departmentId = (int)$get('booking_department_id');
                                                if ($departmentId < 1) {
                                                    return '-';
                                                }

                                                return (string)(BookingDepartment::query()->whereKey($departmentId)->value('name') ?: '-');
                                            }),
                                        /*ButtonAction::make('Enviar')
                                            ->submit(
                                                'submit'
                                            ),*/
                                    ]),

                                Grid::make()
                                    ->columns(['default' => 2])
                                    ->visible(fn(Get $get): bool => PortalTaxistaContext::isPortalPanel() && filled($get('booking_department_id')) && filled($get('tipo_cita_id')) && filled($get('appointment_date')) && filled($get('starts_at')))
                                    ->columnSpanFull()
                                    ->schema([
                                        Textarea::make('notes')
                                            ->label('Notas')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ]),
                            ]),

                        Section::make()
                            ->visible(fn(Get $get): bool => filled($get('booking_department_id')))
                            ->columns([
                                'default' => 1,
                                'md' => 2,
                                'sm' => 2,
                                'lg' => 1,
                                'xl' => 1,
                            ])
                            ->schema([
                                Actions::make([
                                    Action::make('appointment_step_change_department')
                                        ->label(fn(Get $get): string => (string)($get('booking_department_label') ?: 'Departamento'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])->size('xs')
                                        ->outlined()
                                        ->visible(fn(Get $get): bool => filled($get('booking_department_id')))
                                        ->action(function (Set $set): void {
                                            $set('booking_department_id', null);
                                            $set('booking_department_label', null);
                                            $set('tipo_cita_id', null);
                                            $set('tipo_cita_label', null);
                                            $set('title', null);
                                            $set('appointment_date', null);
                                            $set('starts_at', null);
                                            $set('ends_at', null);
                                            $set('disabled_slots', []);
                                            $set('availability_conflicts', null);
                                        }),

                                    Action::make('appointment_step_change_tipo')
                                        ->label(fn(Get $get): string => (string)($get('tipo_cita_label') ?: 'Tipo'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                            $set('tipo_cita_id', null);
                                            $set('tipo_cita_label', null);
                                            $set('title', null);
                                            $set('appointment_date', null);
                                            $set('starts_at', null);
                                            $set('ends_at', null);
                                            $set('disabled_slots', []);
                                            $set('availability_conflicts', null);
                                        })
                                        ->visible(fn(Get $get): bool => filled($get('tipo_cita_id'))),
                                    Action::make('appointment_step_change_date')
                                        ->label(fn(Get $get): string => (string)(Carbon::parse((string)$get('appointment_date'))->format('d/m/Y') ?: 'Fecha'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                            $set('appointment_date', null);
                                            $set('starts_at', null);
                                            $set('ends_at', null);
                                            $set('disabled_slots', []);
                                            $set('availability_conflicts', null);
                                        })
                                        ->visible(fn(Get $get): bool => filled($get('appointment_date'))),

                                    Action::make('appointment_step_change_hora')
                                        ->label(fn(Get $get): string => (string)(Carbon::parse((string)$get('starts_at'))->format('H:i') ?: 'Hora'))
                                        ->icon('heroicon-o-arrow-uturn-left')
                                        ->button()
                                        ->color('red')
                                        ->size('xs')
                                        ->extraAttributes(['class' => 'nova-appointment-choice-pill'])
                                        ->outlined()
                                        ->action(function (Set $set): void {
                                            $set('starts_at', null);
                                            $set('ends_at', null);
                                            $set('disabled_slots', []);
                                            $set('availability_conflicts', null);
                                        })->visible(fn(Get $get): bool => filled($get('starts_at'))),
                                ]),
                            ]),
                    ]),
            ]);
    }
}
