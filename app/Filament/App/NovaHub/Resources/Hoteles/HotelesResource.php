<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\Hoteles;

use App\Events\DocumentoCompleted;
use App\Filament\Support\baseresource;
use App\Filament\Widgets\LocationMap;
use App\Filament\Widgets\LocationMap2;
use App\Filament\Widgets\LocationMapWidget;
use App\Filament\Widgets\mapa2;
use App\Models\Taxi\Blog\Category;
use App\Models\Taxi\Documento;
use App\Models\Taxi\Hotel;
use App\Models\Taxi\Location;
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
use Filament\Schemas\Schema as Form;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Laravel\Pennant\Feature;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;

final class HotelesResource extends baseresource
{
    use InteractsWithMaps;

    protected static ?string $model = Hotel::class;

    protected static array $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];

    protected static array $defaultBoundaries = ['north' => 0 + 0.1, 'south' => 0 - 0.1, 'east' => 0 + 0.1, 'west' => 0 - 0.1];

    protected static ?int $navigationSort = -3;

    protected static string|UnitEnum|null $navigationGroup = 'Central';

    protected static ?string $navigationLabel = 'Hoteles';

    protected static ?string $modelLabel = 'Hotel';

    protected static ?string $modelLabelPlural = 'Hoteles';

    protected static ?string $title = 'Hoteles';

    protected static ?string $recordTitleAttribute = 'title';
    // protected string $view = 'filament.clusters.servicios.hoteles.pages.list-hoteles';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;


    protected function getHeaderWidgets(): array
    {
        return [
            Location2Map::class,

        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }


    public static function getWidgets(): array
    {
        return [
            //mapa2::class,

        ];
    }


    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }


    public static function table(Table $table): Table
    {
        // Configuración de la visualización de la tabla
        return
            $table->columns([
                TextColumn::make('usuarios_direcciones.id')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('title')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->label('Hotel'),
                TextColumn::make('usuario_id.nombre')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->searchable()
                    ->label('Nombre'),

                TextColumn::make('address')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('city')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('street')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('number')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('state')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('country')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('zip')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('lat')
                    ->label('Lat')
                    ->searchable(),
                TextColumn::make('lng')
                    ->label('Lng')
                    ->searchable(),

            ])
                ->filters([


                    ]
                )
                ->filtersLayout(FiltersLayout::Modal)
                ->recordActions([

                    EditAction::make(),

                ])
                ->emptyStateActions([
                    CreateAction::make(),
                ]);
    }

    public static function getRelations(): array
    {
        return [

        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHoteles::route('/'),
            //'create' => Pages\CreateHoteles::route('/create'),
            //'edit' => Pages\EditHoteles::route('/{record}/edit'),
        ];
    }

}
