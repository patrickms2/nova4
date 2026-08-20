<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessGrants;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages\CreateAccessGrant;
use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages\EditAccessGrant;
use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages\ListAccessGrants;
use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages\ViewAccessGrant;
use App\Filament\App\Rentals\Rentals;
use App\Models\AccessGrant;
use App\Services\Domotics\PinGenerator;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use UnitEnum;

class AccessGrantResource extends Resource
{
    protected static ?string $model = AccessGrant::class;

    protected static ?string $cluster = Rentals::class;

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::Key;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Sujeto y propiedad')->schema([
                TextInput::make('name')->label('Nombre')->placeholder('Jardinero - mantenimiento semanal')->required()->maxLength(255),
                Select::make('person_id')->label('Persona')->relationship('person', 'display_name')->searchable(['first_name', 'last_name', 'display_name', 'email'])->preload()->nullable(),
                Select::make('user_id')->label('Cuenta de aplicación')->relationship('user', 'email')->searchable()->preload()->nullable()->helperText('Opcional. La identidad real se vincula mediante Persona.'),
                Select::make('property_id')->label('Propiedad')->relationship('property', 'name')->searchable()->preload()->required(),
            ])->columns(2),
            Section::make('Autorización')->schema([
                Select::make('credentials')->label('Credenciales')->relationship('credentials', 'name')->multiple()->searchable()->preload()->helperText('Credenciales vendor-neutral asociadas a este permiso.'),
                TextInput::make('pin')->label('PIN heredado')->password()->placeholder('Sin cambios / generado automáticamente')->helperText('Compatibilidad con dispositivos existentes. No vuelve a mostrarse tras guardarlo.')->minLength(4)->maxLength(6)->dehydrated(fn (?string $state): bool => filled($state))->unique('access_grants', 'pin', ignoreRecord: true),
                Select::make('accessPoints')->label('Puntos de acceso permitidos')->relationship('accessPoints', 'name')->multiple()->preload()->searchable()->required(),
                Select::make('status')->label('Estado')->options(['active' => 'Activo', 'pending' => 'Pendiente', 'expired' => 'Caducado', 'revoked' => 'Revocado'])->default('active'),
                DateTimePicker::make('valid_from')->label('Válido desde'),
                DateTimePicker::make('valid_until')->label('Válido hasta')->afterOrEqual('valid_from'),
                Toggle::make('is_active')->label('Activo')->default(true),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Quién, dónde y cuándo')->schema([
                TextEntry::make('name')->label('Nombre'),
                TextEntry::make('person.display_name')->label('Persona')->placeholder('Sin persona vinculada'),
                TextEntry::make('user.email')->label('Cuenta')->placeholder('Sin cuenta'),
                TextEntry::make('property.name')->label('Propiedad')->placeholder('Sin propiedad canónica'),
                TextEntry::make('valid_from')->label('Válido desde')->dateTime()->placeholder('Inmediato'),
                TextEntry::make('valid_until')->label('Válido hasta')->dateTime()->placeholder('Sin caducidad'),
                TextEntry::make('status')->label('Estado')->badge()->placeholder('Sin estado'),
                TextEntry::make('is_active')->label('Disponibilidad')->badge()->formatStateUsing(fn (bool $state): string => $state ? 'Activo' : 'Inactivo')->color(fn (bool $state): string => $state ? 'success' : 'gray'),
            ])->columns(2),
            Section::make('Cómo y por dónde')->schema([
                TextEntry::make('credentials.name')->label('Credenciales')->badge()->placeholder('PIN heredado'),
                TextEntry::make('masked_pin')->label('PIN heredado')->state(fn (AccessGrant $record): string => static::maskedPin($record))->fontFamily('mono'),
                TextEntry::make('accessPoints.name')->label('Puntos de acceso')->badge()->placeholder('Sin puntos asignados'),
                TextEntry::make('source_label')->label('Origen')->state(fn (AccessGrant $record): string => $record->source ? class_basename($record->source).' #'.$record->source->getKey() : 'Manual'),
            ])->columns(2),
            Section::make('Actividad reciente')->schema([
                TextEntry::make('domoticsEvents.event_type')->label('Eventos')->badge()->limitList(8)->placeholder('Sin actividad reciente'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('valid_from', 'desc')->columns([
            TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
            TextColumn::make('person.display_name')->label('Persona')->searchable()->placeholder(fn (AccessGrant $record): string => $record->user?->email ?? 'Sin vincular'),
            TextColumn::make('credential')->label('Credencial')->state(fn (AccessGrant $record): string => $record->credentials->first()?->maskedValue() ?? static::maskedPin($record))->fontFamily('mono'),
            TextColumn::make('property.name')->label('Propiedad')->sortable()->searchable()->placeholder('Sin propiedad'),
            TextColumn::make('accessPoints.name')->label('Puntos')->badge()->limitList(3),
            TextColumn::make('valid_from')->label('Desde')->dateTime('d M Y H:i')->sortable()->toggleable(),
            TextColumn::make('valid_until')->label('Hasta')->dateTime('d M Y H:i')->sortable(),
            IconColumn::make('is_active')->label('Activo')->boolean(),
        ])->filters([
            TernaryFilter::make('is_active')->label('Activo'),
            SelectFilter::make('property')->relationship('property', 'name')->searchable()->preload(),
            SelectFilter::make('access_points')->relationship('accessPoints', 'name')->searchable()->multiple(),
        ])->recordActions([
            Action::make('regeneratePin')->label('Regenerar PIN')->icon(Heroicon::ArrowPath)->color('warning')->requiresConfirmation()->modalDescription('El PIN anterior dejará de funcionar en los flujos heredados.')->action(function (AccessGrant $record): void {
                $record->update(['pin' => PinGenerator::generate($record->property)]);
                Notification::make()->title('PIN regenerado')->success()->send();
            }),
            Action::make('deactivate')->label('Desactivar')->icon(Heroicon::NoSymbol)->color('danger')->requiresConfirmation()->visible(fn (AccessGrant $record): bool => $record->is_active)->action(function (AccessGrant $record): void {
                $record->update(['is_active' => false, 'revoked_at' => now(), 'status' => 'revoked']);
                Notification::make()->title('Acceso desactivado')->success()->send();
            }),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['person', 'user', 'property', 'credentials', 'accessPoints', 'source', 'domoticsEvents' => fn (HasMany $query) => $query->latest()->limit(8)]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAccessGrants::route('/'),
            'create' => CreateAccessGrant::route('/create'),
            'view' => ViewAccessGrant::route('/{record}'),
            'edit' => EditAccessGrant::route('/{record}/edit'),
        ];
    }

    private static function maskedPin(AccessGrant $grant): string
    {
        return filled($grant->pin) ? 'PIN '.Str::mask($grant->pin, '•', 0, max(strlen($grant->pin) - 2, 0)) : '—';
    }
}
