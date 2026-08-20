<?php

namespace App\Filament\App\Resources\Taxistas\Schemas;

use App\Models\Taxi\Municipio;
use App\Services\TraccarService;
use App\UserRole;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema as DbSchema;

class TaxistaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('role')
                    ->default(UserRole::SERVICE->value)
                    ->dehydrated(true),
                Tabs::make()
                    ->columnSpanFull()
                    ->tabs([
                        Tabs\Tab::make('Datos del taxista')->schema([
                            TextInput::make('name')
                                ->label('Nombre')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->required()
                                ->maxLength(255)
                                ->unique(ignoreRecord: true),

                            TextInput::make('nif')
                                ->label('NIF')
                                ->required(fn(string $operation): bool => $operation === 'create')
                                ->maxLength(20)
                                ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                                ->unique(fn(string $operation): bool => $operation === 'create'),

                            TextInput::make('licencia')
                                ->label('Licencia')
                                ->maxLength(20)
                                ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtoupper(trim($state)) : null)
                                ->unique(ignoreRecord: true),
                            TextInput::make('phone')
                                ->label('Telefono')
                                ->maxLength(50),

                            TextInput::make('address')
                                ->label('Direccion')
                                ->maxLength(255),

                            Select::make('municipio_id')
                                ->label('Municipio')
                                ->options(fn(): array => DbSchema::hasTable('municipios')
                                    ? Municipio::query()->orderBy('nombre')->pluck('nombre', 'id')->toArray()
                                    : [])
                                ->searchable()
                                ->preload()
                                ->helperText('Se usa para clasificar y filtrar taxistas.'),

                            Select::make('type_id')
                                ->label('Tipo usuario')
                                ->relationship(
                                    'type',
                                    'label',
                                    modifyQueryUsing: fn(Builder $query): Builder => $query->orderBy('label')
                                )
                                ->searchable()
                                ->preload()
                                ->helperText('Opcional. Usa el tipo "Taxista" para segmentar mejor.'),

                            Toggle::make('status')
                                ->label('Activo')
                                ->default(true),
                            Toggle::make('is_featured')
                                ->label('Destacado')
                                ->default(false),
                        ]),
                        Tabs\Tab::make('Seguimiento')->schema([
                            TextInput::make('tracking_uuid')
                                ->label('Codigo de tracking')
                                ->visible(fn(): bool => DbSchema::hasColumn('users', 'tracking_uuid'))
                                ->dehydrated(fn(): bool => DbSchema::hasColumn('users', 'tracking_uuid'))
                                ->maxLength(36)
                                ->live(onBlur: true)
                                ->helperText('Identificador usado para enlazar taxista y dispositivo en Traccar.')
                                ->afterStateUpdated(function (?string $state): void {
                                    if (blank($state)) {
                                        return;
                                    }

                                    $device = app(TraccarService::class)->findTraccarDeviceByUniqueId((string)$state);

                                    Notification::make()
                                        ->title($device ? 'Codigo de tracking verificado en Traccar' : 'Codigo no encontrado en Traccar')
                                        ->color($device ? 'success' : 'warning')
                                        ->send();
                                })
                                ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? strtolower(trim($state)) : null),

                            Toggle::make('is_online')
                                ->label('Seguimiento activo')
                                ->default(false)
                                ->helperText('Marca si el taxista esta reportando posicion en tiempo real.'),

                            TextInput::make('last_lat')
                                ->label('Ultima latitud')
                                ->numeric()
                                ->minValue(-90)
                                ->maxValue(90),

                            TextInput::make('last_lng')
                                ->label('Ultima longitud')
                                ->numeric()
                                ->minValue(-180)
                                ->maxValue(180),

                            DateTimePicker::make('last_location_at')
                                ->label('Ultimo ping')
                                ->seconds(false)
                            ,
                        ])->columns(2),

                        Tabs\Tab::make('Acceso')
                            ->schema([
                                TextInput::make('password')
                                    ->label('Contrasena')
                                    ->password()
                                    ->revealable()
                                    ->minLength(8)
                                    ->required(fn(string $operation): bool => $operation === 'create')
                                    ->dehydrateStateUsing(fn(?string $state): ?string => filled($state) ? $state : null)
                                    ->dehydrated(fn(?string $state): bool => filled($state)),
                            ]),
                    ])
                    ->columns(2),


            ]);
    }
}
