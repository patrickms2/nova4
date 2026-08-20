<?php

namespace App\Filament\App\Pages;


use App\Filament\Widgets\Location2Map;

use App\Models\Taxi\Hotel;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\TipoUsuario;
use Carbon\Carbon;
use Cheesegrits\FilamentGoogleMaps\Actions\GoToAction;
use Cheesegrits\FilamentGoogleMaps\Actions\RadiusAction;
use Cheesegrits\FilamentGoogleMaps\Filters\MapIsFilter;
use Cheesegrits\FilamentGoogleMaps\Filters\RadiusFilter;
use Cheesegrits\FilamentGoogleMaps\Helpers\MapsHelper;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Infolists\Components\Card;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Filament\Tables;
use Filament\Widgets\Concerns\CanPoll;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;
use Cheesegrits\FilamentGoogleMaps\Fields\Map as MapField;
use Cheesegrits\FilamentGoogleMaps\Columns\MapColumn;
use Laravel\Pennant\Feature;

use Filament\Schemas\Schema as Form;
use Cheesegrits\FilamentGoogleMaps\Widgets\MapTableWidget;

class Map extends Page implements HasForms, HasTable
{
    use InteractsWithForms;
    use InteractsWithMaps;
    use InteractsWithActions;
    use InteractsWithTable;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    private static mixed $precision;
    protected string $view = 'filament.pages.map';
    protected static string|null|\UnitEnum $navigationGroup = 'Servicios de Taxista';
    protected static ?string $title = 'Hoteles';
    public array $shops = [];
    public static ?string $mapId = 'hoteles';
    protected static ?bool $filtered = true;
    protected static bool $collapsible = false;
    protected static ?int $zoom = 18;
    protected static ?string $markerAction = 'markerAction';
    protected static ?string $icon = null;
    protected static ?string $minHeight = '80vh';
    //protected string $view = 'filament-google-maps::widgets.filament-google-maps-table-widget';
    protected static ?bool $clustering = true;
    protected static ?bool $fitToBounds = true;
    public ?bool $mapIsFilter = false;
    protected ?array $cachedData = null;
    public string $dataChecksum;
    public ?string $filter = null;
    protected static ?string $maxHeight = null;
    protected static ?array $options = null;
    protected int|string|array $columnSpan = [
        'default' => 1,
        'sm' => 2,
        'md' => 2,
        'lg' => 'full',
    ];
    public array $controls = [
        'mapTypeControl' => true,
        'scaleControl' => true,
        'streetViewControl' => true,
        'rotateControl' => true,
        'fullscreenControl' => true,
        'searchBoxControl' => false,
        'zoomControl' => true,
    ];

    public static function shouldRegisterNavigation(): bool
    {
        if (Feature::active("p_" . strtolower(parent::getNavigationLabel())))
            return Feature::active("p_" . strtolower(parent::getNavigationLabel()));
        else return false;
    }

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


    public function getWidgets(): array
    {
        return [
            mapa2::class,
        ];
    }

    public function getConfig(): array
    {
        $config = [
            'clustering' => self::getClustering(),
            'layers' => [],
            'zoom' => $this->getZoom(),
            'controls' => $this->controls,
            'fit' => $this->getFitToBounds(),
            'markerAction' => $this->getMarkerAction(),
            'gmaps' => MapsHelper::mapsUrl(),
            'mapConfig' => [],
        ];

        // Disable points of interest
//        $config['mapConfig']['styles'] = [
//            [
//                'featureType' => 'poi',
//                'elementType' => 'labels',
//                'stylers' => [
//                    ['visibility' => 'off'],
//                ],
//            ],
//        ];

//        $config['zoom'] = 5;
        $config['center'] = [
            'lat' => 28.921144,
            'lng' => -13.641344,
        ];
        $config = array_merge($config, [
            'mapIsFilter' => $this->mapIsFilter,
        ]);

        return $config;

    }

    public function getMapConfig(): string
    {
        $config = $this->getConfig();
        return json_encode(
            array_merge(
                $config,
            )
        );
    }

    public function hasJs(): bool
    {
        return true;
    }

    public function jsUrl(): string
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../dist/mix-manifest.json'), true);

        return url($manifest['/cheesegrits/filament-google-maps/filament-google-maps-widget.js']);
    }

    public function hasCss(): bool
    {
        return false;
    }

    public function cssUrl(): string
    {
        $manifest = json_decode(file_get_contents(__DIR__ . '/../../dist/mix-manifest.json'), true);

        return url($manifest['/cheesegrits/filament-google-maps/filament-google-maps-widget.css']);
    }


    public function mapIsFilter(): bool
    {
        return $this->mapIsFilter;
    }

    protected function generateDataChecksum(): string
    {
        return md5(json_encode($this->getCachedData()));
    }

    protected function getCachedData(): array
    {
        return $this->cachedData ??= $this->getData();
    }

    protected function getRecords()
    {
        if (static::$filtered) {
            return $this->traitGetTableRecords();
        } else {
            return $this->getTable()->getModel()::all();
        }
    }

    protected function getTableFilters(): array
    {
        return [
            MapIsFilter::make('map'),
        ];
    }

    protected function getTableActions(): array
    {
        return [

            GoToAction::make()
                ->zoom(fn() => 14),

        ];
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50, 100];
    }

    protected function getFilters(): ?array
    {
        return null;
    }

    protected function getZoom(): ?int
    {
        return static::$zoom ?? 8;
    }

    protected function getMaxHeight(): ?string
    {
        return static::$maxHeight;
    }

    protected function getMinHeight(): ?string
    {
        return static::$minHeight;
    }

    protected function getOptions(): ?array
    {
        return static::$options;
    }

    protected function getClustering(): ?bool
    {
        return static::$clustering;
    }

    protected function getFitToBounds(): ?bool
    {
        return static::$fitToBounds;
    }

    protected function getMarkerAction(): ?string
    {
        return static::$markerAction;
    }

    protected function getIcon(): ?string
    {
        return static::$icon;
    }

    protected function getCollapsible(): bool
    {
        return static::$collapsible;
    }


    protected function getViewData(): array
    {
        $mapa = [
            'minHeight' => static::$minHeight,
            'mapConfig' => $this->getConfig(),
            'mapId' => $this->getMapId(),
        ];
        return $mapa;
    }

    private function getMapId()
    {
        return $this->mapId ?? static::$mapId;
    }

    public static function table(Table $table): Table
    {
        return
            $table
                ->columns([
                    TextColumn::make('id')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->label('ID')
                        ->sortable()
                        ->disableClick()
                        ->size('xs')
                        ->extraAttributes(function (Model $record): array {
                            return ['style' => 'width: 75px; '];
                        })
                        ->searchable(),
                    TextColumn::make('usuario.nombre')
                        ->sortable()
                        ->size('xs')
                        ->formatStateUsing(function (Model $record, string $state): string {

                            return $record->id . ' - ' . $state;
                        })
                        ->extraAttributes(function (Model $record): array {
                            return ['style' => 'width: 125px; font-weight: bold', 'wire:click' => new HtmlString(
                                sprintf("setMapCenter(%f,%f,%s); return false;",
                                    round(floatval($record->lat), 8),
                                    round(floatval($record->lng), 8),
                                    $record->usuario->nombre,
                                    14
                                ))];
                        })
                        ->action(function (Action $action, $record) {
                            return $action->alpineClickHandler(function (Model $record) {
                                $latLngFields = $record::getLatLngAttributes();
                                return new HtmlString(
                                    sprintf("setMapCenter(%f,%f,%s); return false;",
                                        round(floatval($record->lat), 8),
                                        round(floatval($record->lng), 8),
                                        $record->usuario->nombre,
                                        14
                                    )
                                );
                            });
                        }
                        )
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

                    TextColumn::make('usuario.email')
                        ->label("Email")
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),

                    TextColumn::make('usuario.tel_fijo')
                        ->label("Tel.")
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),

                    TextColumn::make('usuario.usuario')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),


                ])
                ->filters([

                        Tables\Filters\TernaryFilter::make('usuario.bloqueado'),
                        Tables\Filters\TernaryFilter::make('usuario.estado_id'),

                        Tables\Filters\SelectFilter::make('usuario.tipo_id')
                            ->label('Tipo de Usuario')
                            ->options(function () {
                                // Exclude the current category if editing
                                $query = TipoUsuario::query()->where('estado', '=', 1);
                                return $query->pluck('nombre', 'id');
                            })
                            ->searchable()
                            ->preload(),

                        RadiusFilter::make('radius')
                            ->latitude('lat')
                            ->longitude('lng')
                            ->selectUnit()
                            ->section('Buscar'),
                    ]
                )
                ->filtersLayout(FiltersLayout::Modal)
                ->recordActions([
                    //EditAction::make(),


                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                        BulkAction::make('Cambiar TIPO')
                            ->icon('heroicon-m-pencil-square')
                            ->form([
                                Select::make('tipo_id')
                                    ->label('Tipos')
                                    ->default(2)
                                    ->options(function () {
                                        // Exclude the current category if editing
                                        $query = TipoUsuario::query()->where('estado', '=', 1);
                                        return $query->pluck('nombre', 'id');
                                    })
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['tipo_id' => $data['tipo_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),


                        BulkAction::make('featureSelected')
                            ->label('Feature Selected')
                            ->icon('heroicon-o-star')
                            ->color('warning')
                            ->action(function ($records): void {
                                foreach ($records as $record) {
                                    $record->update(['is_featured' => true]);
                                }

                                Notification::make()
                                    ->title('Selected posts featured successfully')
                                    ->success()
                                    ->send();
                            })
                            ->requiresConfirmation()
                            ->visible(),
                        BulkAction::make('activate')
                            ->label('Activar')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(fn(Collection $records) => $records->each->update(['estado_id' => 1])),
                        BulkAction::make('deactivate')
                            ->label('Desactivar')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(fn(Collection $records) => $records->each->update(['estado_id' => 0])),
                    ])
                ])
                ->emptyStateActions([
                    CreateAction::make(),
                ]);
    }

    public function filtersForm(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        Select::make('businessCustomersOnly')
                            ->boolean(),
                        DatePicker::make('startDate')
                            ->maxDate(fn(Get $get) => $get('endDate') ?: now()),
                        DatePicker::make('endDate')
                            ->minDate(fn(Get $get) => $get('startDate') ?: now())
                            ->maxDate(now()),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    protected function getTableRecordAction(): ?string
    {
        return 'setmapcenter';
    }
}
