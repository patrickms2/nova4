<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessPoints;

use App\Enums\AccessPointType;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages\CreateAccessPoint;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages\EditAccessPoint;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages\ListAccessPoints;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages\ViewAccessPoint;
use App\Filament\App\Rentals\Rentals;
use App\Jobs\CloseAccessPoint;
use App\Jobs\OpenAccessPoint;
use App\Models\AccessPoint;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UnitEnum;

class AccessPointResource extends Resource
{
    protected static ?string $model = AccessPoint::class;

    protected static ?string $cluster = Rentals::class;

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::LockOpen;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 3;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos del punto de acceso')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->placeholder('Portón principal')
                            ->required()
                            ->maxLength(255),
                        Select::make('type')
                            ->label('Tipo')
                            ->options(AccessPointType::class)
                            ->required(),
                        Select::make('property_id')
                            ->label('Propiedad')
                            ->relationship('property', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('device_id')
                            ->label('Dispositivo asociado')
                            ->relationship('device', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Dispositivo que acciona este punto de acceso'),
                        TextInput::make('location')
                            ->label('Ubicación')
                            ->maxLength(255)
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('Activo')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Punto de acceso')->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('property.name')->label('Propiedad'),
                    TextEntry::make('type')->label('Tipo')->badge(),
                    TextEntry::make('location')->label('Ubicación')->placeholder('Sin ubicación'),
                    TextEntry::make('device.name')->label('Dispositivo')->placeholder('Sin dispositivo'),
                    TextEntry::make('device.status')->label('Salud del dispositivo')->badge()->placeholder('Desconocida'),
                    TextEntry::make('active_grants_count')->label('Permisos activos'),
                    TextEntry::make('is_active')->label('Activo')->boolean(),
                ])->columns(2),
                Section::make('Actividad reciente')->schema([
                    TextEntry::make('domoticsEvents.event_type')->label('Eventos')->badge()->limitList(8)->placeholder('Sin eventos recientes'),
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
                TextColumn::make('device.name')
                    ->label('Dispositivo'),
                TextColumn::make('device.status')->label('Conexión')->badge()->placeholder('Desconocida'),
                TextColumn::make('active_grants_count')->label('Permisos activos')->numeric()->sortable(),
                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('open')
                    ->label(fn (AccessPoint $record): string => $record->type === 'light' ? 'Encender' : 'Abrir')
                    ->icon(Heroicon::LockOpen)
                    ->color('success')
                    ->visible(fn (AccessPoint $record): bool => $record->is_active)
                    ->authorize(fn (AccessPoint $record): bool => auth()->user()?->can('open', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (AccessPoint $record): void {
                        dispatch_sync(new OpenAccessPoint($record, auth()->user()));
                        Notification::make()->title('Orden de apertura ejecutada')->success()->send();
                    }),
                Action::make('close')
                    ->label(fn (AccessPoint $record): string => $record->type === 'light' ? 'Apagar' : 'Cerrar')
                    ->icon(Heroicon::LockClosed)
                    ->color('danger')
                    ->visible(fn (AccessPoint $record): bool => $record->is_active)
                    ->authorize(fn (AccessPoint $record): bool => auth()->user()?->can('close', $record) ?? false)
                    ->requiresConfirmation()
                    ->action(function (AccessPoint $record): void {
                        dispatch_sync(new CloseAccessPoint($record, auth()->user()));
                        Notification::make()->title('Orden de cierre ejecutada')->success()->send();
                    }),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['property', 'device'])->withCount([
            'accessGrants as active_grants_count' => fn (Builder $query) => $query->active(),
        ])->with(['domoticsEvents' => fn (HasMany $query) => $query->latest()->limit(8)]);
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
            'index' => ListAccessPoints::route('/'),
            'create' => CreateAccessPoint::route('/create'),
            'view' => ViewAccessPoint::route('/{record}'),
            'edit' => EditAccessPoint::route('/{record}/edit'),
        ];
    }
}
