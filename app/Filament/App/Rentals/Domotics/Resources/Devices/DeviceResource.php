<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Devices;

use App\Enums\DeviceStatus;
use App\Enums\DeviceType;
use App\Filament\App\Rentals\Domotics\Resources\Devices\Pages\CreateDevice;
use App\Filament\App\Rentals\Domotics\Resources\Devices\Pages\EditDevice;
use App\Filament\App\Rentals\Domotics\Resources\Devices\Pages\ListDevices;
use App\Filament\App\Rentals\Domotics\Resources\Devices\Pages\ViewDevice;
use App\Filament\App\Rentals\Rentals;
use App\Models\Device;
use BackedEnum;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UnitEnum;

class DeviceResource extends Resource
{
    protected static ?string $model = Device::class;

    protected static ?string $cluster = Rentals::class;

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::CpuChip;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del dispositivo')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(DeviceType::class)
                            ->required(),
                        Select::make('property_id')
                            ->label('Propiedad')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('identifier')
                            ->label('Identificador físico')
                            ->helperText('Serial, MAC o entity_id de Home Assistant')
                            ->required()
                            ->maxLength(255),
                        Select::make('status')
                            ->label('Estado')
                            ->options(DeviceStatus::class)
                            ->default('unknown')
                            ->required(),
                        KeyValue::make('meta')
                            ->label('Metadatos')
                            ->keyLabel('Clave')
                            ->valueLabel('Valor')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Salud del dispositivo')->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('property.name')->label('Propiedad'),
                    TextEntry::make('type')->label('Tipo')->badge(),
                    TextEntry::make('identifier')->label('Identificador'),
                    TextEntry::make('status')->label('Estado')->badge(),
                    TextEntry::make('last_seen_at')->label('Última conexión')->dateTime()->placeholder('Sin datos'),
                    TextEntry::make('access_points_count')->label('Puntos de acceso'),
                ])->columns(2),
                Section::make('Puntos y actividad')->schema([
                    TextEntry::make('accessPoints.name')->label('Puntos de acceso')->badge()->placeholder('Sin puntos'),
                    TextEntry::make('domoticsEvents.event_type')->label('Eventos recientes')->badge()->limitList(8)->placeholder('Sin eventos'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge(),
                TextColumn::make('property.name')->label('Propiedad')->searchable()->sortable(),
                TextColumn::make('identifier')
                    ->label('Identificador')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),
                TextColumn::make('last_seen_at')
                    ->label('Última conexión')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('access_points_count')->label('Puntos')->counts('accessPoints')->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options(DeviceType::class)
                    ->multiple(),
                SelectFilter::make('status')
                    ->options(DeviceStatus::class)
                    ->multiple(),
                SelectFilter::make('property')->relationship('property', 'name')->searchable()->preload(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['property', 'accessPoints'])->withCount('accessPoints')->with([
            'domoticsEvents' => fn (HasMany $query) => $query->latest()->limit(8),
        ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDevices::route('/'),
            'create' => CreateDevice::route('/create'),
            'view' => ViewDevice::route('/{record}'),
            'edit' => EditDevice::route('/{record}/edit'),
        ];
    }
}
