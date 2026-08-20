<?php

declare(strict_types=1);

namespace App\Filament\Forms;

use App\Enums\CitaStatus;
use App\Filament\Components\Forms\Fields\CalendarInput;
use App\Models\Taxi\Cita;
use App\Models\Taxi\Departamento;
use App\Models\Taxi\OpeningTime;
use App\Services\SlotService;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;

// Import necesario

final class CitaForm
{
    public static function make(): array
    {

        return [
            Section::make()
                ->columns(3)
                ->schema([
                    Group::make()
                        ->schema([
                            Section::make('')
                                ->hiddenlabel()
                                ->extraAttributes(['class' => 'text-sm',
                                    'style' => 'font-size: small; font-weight: thin']) // Aplica negritas
                                ->schema([

                                    ToggleButtons::make('departamento_id')
                                        ->visible(fn (Get $get): bool => (bool) ($get('departamento_id') === null || ! $get('departamento_id')) ? true : false)
                                        ->options(fn () => OpeningTime::departamentosConHorariosT())
                                                                // ->default(fn () => OpeningTime::departamentoConHorarios(auth()->id())->toArray()[0])
                                        ->required()

                                                                // ->colors(fn () => OpeningTime::departamentosConHorariosColores())
                                                                /*->icons([
                                            1 => 'heroicon-m-check',
                                            0 => 'heroicon-m-x-mark',
                                        2 => 'heroicon-m-x-mark',
                                        3 => 'heroicon-m-x-mark',
                                        ])*/
                                        ->hiddenLabel()
                                        ->extraAttributes(['class' => 'text-xxl',
                                            'style' => 'font-size: xxl; font-weight: bold']) // Aplica negritas
                                        ->live()
                                        ->inline()
                                        ->columns(2)
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (! $state | $state === null) {
                                                return;
                                            }
                                            $departamentoId = $get('departamento_id');
                                            if (! $departamentoId) {
                                                return;
                                            }

                                            $set('appointment_date', null);
                                            $set('slot_id', null);
                                            $set('departamento', Departamento::find($departamentoId)->nombre ?? null);

                                        })
                                        ->helperText('Seleccione departamento para su cita')
                                        ->columnSpanFull()
                                        ->reactive(),

                                    CalendarInput::make('appointment_date')
                                        ->calendarLocale('es')
                                        ->extraAttributes(['class' => 'text-xl',
                                            'style' => 'font-weight: bold']) // Aplica negritas
                                        ->reactive()
                                        ->live()
                                        ->hiddenlabel()
                                        ->visible(fn (Get $get): bool => (bool) ($get('departamento_id') === null || ! $get('departamento_id')) ? false : true)
                                        // ->live()
                                        ->extraAttributes(fn (Get $get) => [
                                            'wire:key' => 'calendar-'.(string) $get('departamento_id'),
                                        ])
                                        ->minDate(today())
                                        ->disabledDates(function (Get $get): array {
                                            $departamentoId = $get('departamento_id');
                                            if (! $departamentoId) {
                                                return [];
                                            }

                                            $allowed = OpeningTime::where('departamento_id', $departamentoId)
                                                ->get()
                                                ->flatMap(fn ($h) => $h->days ?? [])
                                                ->map(fn ($d) => is_numeric($d) ? (int) $d : match (mb_strtolower((string) $d)) {
                                                    'domingo' => 0, 'lunes' => 1, 'martes' => 2,
                                                    'miercoles', 'miércoles' => 3, 'jueves' => 4,
                                                    'viernes' => 5, 'sabado', 'sábado' => 6,
                                                    default => null,
                                                })
                                                ->filter(fn ($v) => $v !== null)
                                                ->unique()
                                                ->all();
                                            $start = now()->startOfDay();
                                            $end = now()->addMonths(1)->endOfDay();

                                            $toDisable = [];
                                            $day = $start->clone();
                                            $i = 0;
                                            while ($day->lte($end) && $i < 20) {
                                                $enabledBySchedule = ! empty($allowed) && in_array($day->dayOfWeek, $allowed, true);
                                                if (! $enabledBySchedule) {
                                                    // Día fuera del horario → deshabilitar

                                                    $toDisable[] = $day->toDateString();
                                                } else {

                                                    // Día dentro del horario → verificar si existen slots disponibles
                                                    $hasSlots = SlotService::availableFor(
                                                        $departamentoId,
                                                        $day->dayOfWeek,
                                                        $day->copy()
                                                    )->isNotEmpty();

                                                    if (! $hasSlots) {
                                                        $toDisable[] = $day->toDateString();
                                                    }
                                                }
                                                $i++;
                                                $day->addDay();
                                            }

                                            return $toDisable;
                                        })
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if (! $state) {
                                                return;
                                            }

                                            $departamentoId = $get('departamento_id');
                                            if (! $departamentoId) {
                                                return;
                                            }

                                            $allowed = OpeningTime::where('departamento_id', $departamentoId)
                                                ->get()
                                                ->flatMap(fn ($h) => $h->days ?? [])
                                                ->map(fn ($d) => is_numeric($d) ? (int) $d : match (mb_strtolower((string) $d)) {
                                                    'domingo' => 0, 'lunes' => 1, 'martes' => 2,
                                                    'miercoles', 'miércoles' => 3, 'jueves' => 4,
                                                    'viernes' => 5, 'sabado', 'sábado' => 6,
                                                    default => null,
                                                })
                                                ->filter(fn ($v) => $v !== null)
                                                ->unique()
                                                ->all();

                                            $day = Carbon::parse($state)->dayOfWeek;
                                            if (! in_array($day, $allowed, true)) {
                                                $set('appointment_date', null);
                                            } else {
                                                // Si cambia la fecha válida, limpiamos el slot para forzar recálculo
                                                $set('slot_id', null);
                                            }

                                            // $get('tab', 1);
                                        })
                                        ->reactive(),

                                ]),
                            Section::make('Notas de su Cita')
                                ->visible(fn (Get $get): bool => (bool) ($get('departamento_id') === '' || $get('departamento_id') !== null || ! $get('departamento_id')) ? false : true)
                                ->schema([
                                    RepeatableEntry::make('notes')
                                        ->label('')
                                        ->schema([
                                            TextEntry::make('id')
                                                ->label(''),
                                            TextEntry::make('note')
                                                ->label('')
                                                ->size('lg')
                                                ->extraAttributes([
                                                    'class' => 'font-semibold',
                                                ]),
                                        ]),
                                ])
                                ->columns(2),

                        ])
                        ->columnSpan(['lg' => fn (Get $get) => ! $get('departamento_id') ? 3 : 2]),

                    Group::make()
                        ->visible(fn (Get $get): bool => (bool) ($get('departamento_id') === null || ! $get('departamento_id')) ? false : true)
                        ->schema([
                            Section::make('Datos de Cita')
                                ->columns(1)
                                ->schema([
                                    Hidden::make('id'),
                                    Hidden::make('usuario_id')->default(fn (Get $get) => auth()->user()->id),
                                    Hidden::make('departamento_id'),
                                    // ->inline(),
                                    TextEntry::make('departamento_id2')
                                        ->label('Departamento')
                                        ->state(fn (Get $get) => Departamento::find($get('departamento_id'))->nombre ?? null)
                                        ->suffixActions([

                                            Action::make('departamento')
                                                ->icon('heroicon-m-x-circle')
                                                ->size('md')
                                                ->action(function ($component, Set $set, $state, ?Cita $record, Get $get) {
                                                    if ($record !== null) {
                                                        $record->departamento_id = null;
                                                        $record->appointment_date = null;
                                                        $record->slot_id = null;
                                                    }
                                                    $set('appointment_date', null);
                                                    $set('departamento_id', null);
                                                    $set('slot_id', null);

                                                    return $get('departamento_id');
                                                }),
                                        ])
                                        ->badge()
                                        ->icon('heroicon-o-rectangle-stack')
                                        ->iconColor('primary'),

                                    TextEntry::make('appointment_date2')
                                        ->label('Fecha de Cita')
                                        ->visible(fn (Get $get) => $get('appointment_date'))
                                        ->state(fn (Get $get) => $get('appointment_date'))
                                        ->suffixActions([

                                            Action::make('fecha')
                                                ->icon('heroicon-m-x-circle')
                                                ->size('md')
                                                ->visible(fn (Get $get) => $get('appointment_date'))
                                                ->action(function ($component, Set $set, $state, ?Cita $record, Get $get) {
                                                    if ($record !== null) {
                                                        $record->appointment_date = null;
                                                        $record->slot_id = null;
                                                    }
                                                    $set('appointment_date', null);
                                                    $set('slot_id', null);

                                                    return $get('appointment_date');
                                                }),
                                        ])
                                        ->badge()
                                        ->icon('heroicon-o-rectangle-stack')
                                        ->iconColor('primary'),

                                    TextEntry::make('slot_id2')
                                        ->label('Hora de Cita')
                                        ->visible(fn (Get $get) => $get('slot_id'))
                                        ->suffixActions([

                                            Action::make('departamento')
                                                ->icon('heroicon-m-x-circle')
                                                ->size('md')
                                                ->visible(fn (Get $get) => $get('slot_id'))
                                                ->action(function ($component, Set $set, $state, ?Cita $record, Get $get) {
                                                    if ($record !== null) {
                                                        $record->slot_id = null;
                                                    }
                                                    $set('slot_id', null);

                                                    return $get('slot_id');
                                                }),
                                        ])
                                        ->badge()
                                        ->icon('heroicon-o-rectangle-stack')
                                        ->iconColor('primary')

                                        ->extraAttributes(['class' => 'text-xl',
                                            'style' => 'color: red; font-weight: normal']) // Aplica negritas

                                        ->state(fn (Get $get) => $get('slot_id')),
                                    Select::make('tipo_id')
                                        ->label('Tipo')
                                        ->relationship('tipo', 'nombre')
                                        ->visible(fn (Get $get): bool => (bool) $get('departamento_id'))
                                        // ->inline()
                                        ->preload()
                                        ->searchable()
                                        ->default(6),

                                    Select::make('usuario_id')
                                        ->label('Usuario')
                                        ->relationship('usuario', 'nombre')
                                        // ->visible(fn (Get $get): bool => (bool) $get('slot_id') && (bool) $get('slot_id'))
                                        ->visible(false)
                                        ->required()
                                        ->default(fn (Get $get) => auth()->user()->id)
                                        ->live()
                                        ->searchable()
                                        ->reactive(),

                                    Select::make('status')
                                        ->label('Estado')
                                        ->visible(true)
                                        ->options(CitaStatus::class)
                                        // ->visible(fn (Get $get): bool => (bool) $get('slot_id') && (bool) $get('slot_id'))
                                        // ->inline()
                                        ->searchable()

                                        ->default(CitaStatus::pendiente),
                                ]),
                        ])
                        ->columnSpan(['lg' => 1]),

                    Group::make()
                        ->columnSpanFull()
                        ->visible(fn (Get $get): bool => (bool) ($get('departamento_id') === null || ! $get('departamento_id')) ? false : true)
                        ->schema([

                            Section::make(fn (Get $get): string => (string) 'Citas Departamento '.$get('departamento'))
                                                   // ->label('Fechas disponibles')
                                ->columnSpanFull()
                                ->visible(fn (Get $get) => $get('departamento_id'))
                                                   // ->collapsible()
                                ->schema([ToggleButtons::make('slot_id')
                                    ->label(__('Seleccione la hora'))
                                    ->visible(fn (Get $get) => $get('appointment_date'))
                                    ->extraAttributes(['class' => 'text-xl',
                                        'style' => 'font-weight: bold;']) // Aplica negritas                                                        ->inline()
                                    ->inline()
                                    ->reactive()
                                    ->options(function (Get $get, $state) {
                                        $departamentoId = $get('departamento_id');
                                        $appointmentDate = $get('appointment_date');
                                        $appointmentTime = $state;

                                        if (! $departamentoId || ! $appointmentDate) {
                                            return [];
                                        }

                                        $date = Carbon::parse($appointmentDate);

                                        $horariosHabilitados = SlotService::availableFor(
                                            $departamentoId,
                                            $date->dayOfWeek,
                                            $date
                                        )->pluck('time', 'id')->toArray();
                                        if ($appointmentTime && ! in_array($appointmentTime, $horariosHabilitados, true)) {
                                            $horariosHabilitados[$appointmentTime] = $appointmentTime;
                                        }

                                        return collect($horariosHabilitados)->mapWithKeys(fn ($time, $key) => [$key => $time]);

                                    })
                                    ->colors(
                                        [
                                            'bg-primary-500' => 'bg-red-500 text-white',
                                            'text-zinc-800' => 'bg-warning text-white',

                                            0 => 'danger',
                                            1 => 'gray',
                                        ]
                                    )
                                    ->required(),

                                ]),

                        ]),
                ]),
            // ->collapsed(fn (Get $get): bool => (bool) $get('appointment_date') )
            // ->collapsible(),

        ];
    }
}
