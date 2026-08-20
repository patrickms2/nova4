<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Hoteles;

use App\Filament\App\Resources\Hoteles\Pages\CreateHoteles;
use App\Filament\App\Resources\Hoteles\Pages\EditHoteles;
use App\Filament\App\Resources\Hoteles\Pages\ListHoteles;
use App\Filament\App\Resources\Hoteles\Schemas\HotelesForm;
use App\Filament\Support\baseresource;
use App\Models\Taxi\UsuarioDireccion;
use Filament\Schemas\Schema as Form;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Documento;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\TipoUsuario;
use App\Models\Taxi\Usuario;
use BackedEnum;
use Cheesegrits\FilamentGoogleMaps\Actions\GoToAction;
use Cheesegrits\FilamentGoogleMaps\Actions\RadiusAction;
use Cheesegrits\FilamentGoogleMaps\Columns\MapColumn;
use Cheesegrits\FilamentGoogleMaps\Fields\Geocomplete;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Cheesegrits\FilamentGoogleMaps\Filters\MapIsFilter;
use Cheesegrits\FilamentGoogleMaps\Filters\RadiusFilter;
use Cheesegrits\FilamentGoogleMaps\Helpers\MapsHelper;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;

class HotelesResource extends baseresource
{
    protected static ?string $model = UsuarioDireccion::class;

    protected static bool $isScopedToTenant = false;

    protected static bool $isGloballySearchable = true;

    protected static ?string $modelLabel = 'Hotel';

    protected static ?string $pluralModelLabel = 'Hoteles';

    protected static string|\UnitEnum|null $navigationGroup = 'Departamentos';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $navigationLabel = 'Hoteles';

    protected static ?int $navigationSort = 11;

    protected static function shouldRestrictToCurrentUser(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return HotelesForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('street')
                    ->label('Dirección')
                    ->sortable()
                    ->searchable()
                    ->toggleable()
                    ->description(fn($record): ?string => $record->city),
                TextColumn::make('city')
                    ->label('Ciudad')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('state')
                    ->label('Provincia')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('lat')
                    ->label('Lat')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('lng')
                    ->label('Lng')
                    ->toggleable()
                    ->searchable(),
                TextColumn::make('website')
                    ->label('Web')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->url(fn($record): ?string => $record->website, shouldOpenInNewTab: true)
                    ->limit(30),
            ])
            ->defaultSort('name')
            ->filters([])
            ->filtersLayout(FiltersLayout::Modal)
            ->recordActions([
                EditAction::make(),
                GoToAction::make(),
            ])
            ->emptyStateActions([
                CreateAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHoteles::route('/'),
            'create' => CreateHoteles::route('/create'),
            'edit' => EditHoteles::route('/{record}/edit'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\HotelesMapWidget::class,
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['title', 'address', 'city', 'name'];
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return (string) ($record->title ?: $record->name ?: $record->address ?: 'Hotel');
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return [
            'Direccion' => (string)($record->address ?? '-'),
            'Ciudad' => (string)($record->city ?? '-'),
        ];
    }
}
