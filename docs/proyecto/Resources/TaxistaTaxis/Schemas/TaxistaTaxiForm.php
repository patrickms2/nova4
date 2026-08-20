<?php

namespace App\Filament\App\Resources\TaxistaTaxis\Schemas;

use App\Services\TraccarService;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema as DbSchema;

class TaxistaTaxiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Taxi')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Tabs::make('TaxiTabs')
                            ->columnSpanFull()
                            ->tabs([
                                Tab::make('Datos taxi')
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
                                            ->required(),

                                        TextInput::make('license_plate')
                                            ->label('Matricula')
                                            ->required()
                                            ->maxLength(20)
                                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null),

                                        TextInput::make('vehicle_brand')
                                            ->label('Marca')
                                            ->maxLength(255),

                                        TextInput::make('vehicle_model')
                                            ->label('Modelo')
                                            ->maxLength(255),

                                        Select::make('vehicle_type')
                                            ->label('Tipo')
                                            ->options([
                                                'berlina' => 'Berlina',
                                                'familiar' => 'Familiar',
                                                'van' => 'Van',
                                                'adaptado' => 'Adaptado',
                                                'otro' => 'Otro',
                                            ]),

                                        TextInput::make('seats')
                                            ->label('Plazas')
                                            ->numeric()
                                            ->minValue(1)
                                            ->maxValue(9),

                                        TextInput::make('municipality')
                                            ->label('Municipio')
                                            ->maxLength(255),

                                        Select::make('status')
                                            ->label('Estado')
                                            ->required()
                                            ->default('activo')
                                            ->options([
                                                'activo' => 'Activo',
                                                'mantenimiento' => 'Mantenimiento',
                                                'baja' => 'Baja',
                                            ]),

                                        Toggle::make('is_accessible')
                                            ->label('Adaptado PMR')
                                            ->default(false),

                                        Select::make('tracking_mode')
                                            ->label('Seguimiento')
                                            ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_mode'))
                                            ->dehydrated(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_mode'))
                                            ->options([
                                                'real' => 'Activado',
                                                'simulated' => 'Simulacion',
                                                'disabled' => 'Desactivado',
                                            ])
                                            ->default('real')
                                            ->live(),

                                        Textarea::make('notes')
                                            ->label('Notas')
                                            ->rows(3)
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),

                                Tab::make('Datos seguimiento')
                                    ->visible(fn (Get $get): bool => self::isTrackingTabVisible($get))
                                    ->schema([
                                        Hidden::make('tracking_uuid_seed')
                                            ->dehydrated(false)
                                            ->default(fn (): string => (string) Str::ulid()),

                                        Placeholder::make('tracking_code_preview')
                                            ->label('Codigo tracking sugerido')
                                            ->visible(fn (Get $get): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid') && self::isTrackingSuggestionVisible($get))
                                            ->content(fn ($record, Get $get): string => self::resolveTrackingCodeSuggestion(
                                                currentTrackingUuid: (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')
                                                    ? (($record && array_key_exists('tracking_uuid', $record->getAttributes()))
                                                        ? $record->getAttributes()['tracking_uuid']
                                                        : null)
                                                    : null),
                                                formTrackingUuid: $get('tracking_uuid') ?: $get('tracking_uuid_seed'),
                                            )),

                                        TextInput::make('tracking_uuid')
                                            ->label('Codigo tracking')
                                            ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid'))
                                            ->dehydrated(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_uuid'))
                                            ->default(fn ($record): ?string => filled($record?->tracking_uuid) ? (string) $record->tracking_uuid : (string) Str::ulid())
                                            ->maxLength(64)
                                            ->live()
                                            ->suffixActions([
                                                Action::make('pasteTrackingSuggestion')
                                                    ->icon('heroicon-m-arrow-down-on-square-stack')
                                                    ->tooltip('Pegar sugerencia')
                                                    ->visible(fn (Get $get): bool => self::isTrackingSuggestionVisible($get))
                                                    ->action(function (Get $get, Set $set, $record): void {
                                                        $set('tracking_uuid', self::resolveTrackingCodeSuggestion(
                                                            currentTrackingUuid: (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')
                                                                ? (($record && array_key_exists('tracking_uuid', $record->getAttributes()))
                                                                    ? $record->getAttributes()['tracking_uuid']
                                                                    : null)
                                                                : null),
                                                            formTrackingUuid: $get('tracking_uuid_seed'),
                                                        ));
                                                    }),
                                                Action::make('regenerateTrackingCode')
                                                    ->icon('heroicon-m-arrow-path')
                                                    ->tooltip('Regenerar codigo')
                                                    ->visible(fn (Get $get): bool => self::isTrackingSuggestionVisible($get))
                                                    ->action(function (Set $set): void {
                                                        $set('tracking_uuid', (string) Str::ulid());
                                                    }),
                                            ], isInline: true)
                                            ->helperText('Pega el identificador real del dispositivo en Traccar (UUID/ULID). No uses matricula como codigo de tracking.')
                                            ->afterStateUpdated(function (?string $state): void {
                                                if (blank($state)) {
                                                    return;
                                                }

                                                $device = app(TraccarService::class)->findTraccarDeviceByUniqueId((string) $state);

                                                Notification::make()
                                                    ->title($device ? 'Codigo de taxi verificado en Traccar' : 'Codigo de taxi no encontrado en Traccar')
                                                    ->color($device ? 'success' : 'warning')
                                                    ->send();
                                            })
                                            ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? trim((string) $state) : null),

                                        Toggle::make('tracking_simulation_enabled')
                                            ->label('Simulacion activa')
                                            ->visible(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled'))
                                            ->dehydrated(fn (): bool => DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled'))
                                            ->default(false)
                                            ->helperText('Activa/desactiva simulacion para pruebas de tracking.'),

                                        TextInput::make('current_lat')
                                            ->label('Latitud')
                                            ->numeric()
                                            ->minValue(-90)
                                            ->maxValue(90),

                                        TextInput::make('current_lng')
                                            ->label('Longitud')
                                            ->numeric()
                                            ->minValue(-180)
                                            ->maxValue(180),

                                        DateTimePicker::make('last_located_at')
                                            ->label('Ultima localizacion')
                                            ->seconds(false),
                                    ])
                                    ->columns(2),
                            ]),
                    ])
                    ->columns(2),
            ]);
    }

    private static function resolveTrackingCodeSuggestion(mixed $currentTrackingUuid, mixed $formTrackingUuid): string
    {
        $current = self::normalizeTrackingCode($currentTrackingUuid);
        if (self::isValidTrackingIdentifier($current)) {
            return $current;
        }

        $fromForm = self::normalizeTrackingCode($formTrackingUuid);
        if (self::isValidTrackingIdentifier($fromForm)) {
            return $fromForm;
        }

        return (string) Str::ulid();
    }

    private static function isTrackingTabVisible(Get $get): bool
    {
        if (! DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
            return false;
        }

        if (! DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
            return true;
        }

        return (string) ($get('tracking_mode') ?? 'real') !== 'disabled';
    }

    private static function isTrackingSuggestionVisible(Get $get): bool
    {
        return blank($get('tracking_uuid'));
    }

    private static function normalizeTrackingCode(mixed $value): string
    {
        if (! is_string($value)) {
            return '';
        }

        return trim($value);
    }

    private static function isValidTrackingIdentifier(string $identifier): bool
    {
        if ($identifier === '') {
            return false;
        }

        $isUuid = Str::isUuid($identifier);
        $isUlid = Str::isUlid($identifier);

        return $isUuid || $isUlid;
    }
}
