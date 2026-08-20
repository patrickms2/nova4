<?php

namespace App\Filament\App\Resources\Hoteles\Schemas;

use App\Filament\Components\Forms\Fields\CalendarInput;
use App\Models\BookingDepartment;
use App\Models\Taxi\TipoCitas;
use App\Models\Taxi\UsuarioDireccion;
use App\Models\TaxistaAppointment;
use App\Services\SlotService;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
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
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use App\Events\DocumentoCompleted;
use App\Filament\Support\baseresource;
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
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ReplicateAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Schemas\Schema as Form;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Laravel\Pennant\Feature;
use UnitEnum;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;

class HotelesForm
{

    // protected static string | BackedEnum | null $navigationIcon  = 'heroicon-o-rectangle-stack';
    public static function configure(Schema $form): Schema
    {
        $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];
        $defaultBoundaries = ['north' => $defaultCenter['lat'] + 0.1, 'south' => $defaultCenter['lat'] - 0.1, 'east' => $defaultCenter['lng'] + 0.1, 'west' => $defaultCenter['lng'] - 0.1];

        return $form->schema([

            Section::make('Datos del hotel')
                ->columns(3)
                ->schema([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->maxLength(256),
                    TextInput::make('phone')
                        ->label('Teléfono')
                        ->tel()
                        ->maxLength(50),
                    TextInput::make('website')
                        ->label('Web')
                        ->url()
                        ->maxLength(512),

                    TextInput::make('place_id')
                        ->label('Place Id')
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),

            Section::make('Dirección')
                ->columns(2)
                ->schema([
                    TextInput::make('street')
                        ->label('Calle')
                        ->extraInputAttributes([
                            'data-google-field' => '{street_number} {route}, {sublocality_level_1}',
                        ])
                        ->maxLength(255),
                    TextInput::make('number')
                        ->label('Número')
                        ->maxLength(20),
                    TextInput::make('zip')
                        ->label('C.P.')
                        ->maxLength(20),
                    TextInput::make('city')
                        ->label('Ciudad')
                        ->maxLength(255),
                    TextInput::make('state')
                        ->label('Provincia')
                        ->maxLength(255),
                    TextInput::make('country')
                        ->label('País')
                        ->default('España')
                        ->maxLength(255),
                ]),

            Section::make('Coordenadas')
                ->columns(2)
                ->schema([
                    TextInput::make('lat')
                        ->label('Latitud')
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $set('location', [
                                'lat' => floatVal($state),
                                'lng' => floatVal($get('lng')),
                            ]);
                        })
                        ->lazy()
                        ->maxLength(32),

                    TextInput::make('lng')
                        ->label('Longitud')
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $set('location', [
                                'lat' => floatval($get('lat')),
                                'lng' => floatVal($state),
                            ]);
                        })
                        ->lazy()
                        ->maxLength(32),

                    Textarea::make('description')
                        ->label('Observaciones')
                        ->rows(2)
                        ->columnSpanFull(),
                ]),

            Section::make('Mapa')
                ->description('Arrastra el marcador para corregir la ubicación')
                ->schema([
                    Map::make('location')
                        ->label('Ubicación en mapa')
                        ->defaultLocation($defaultCenter)
                        ->autocomplete('address')
                        ->autocompleteReverse(true)
                        ->reverseGeocode([
                            'city' => '%L',
                            'zip' => '%z',
                            'state' => '%A1',
                            'street' => '%n %S',
                            'number' => '%N',
                            'country' => '%C',
                        ])
                        ->draggable()
                        ->clickable()
                        ->defaultZoom(15)
                        ->geolocate()
                        ->geolocateLabel('Mi ubicación')
                        ->columnSpanFull()
                        ->height('400px'),

                    Geocomplete::make('address')
                        ->label('Buscar dirección')
                        ->types(['establishment'])
                        ->filterName('formatted_address')
                        ->placeField('name')
                        ->reactive()
                        ->updateLatLng()
                        ->afterStateUpdated(function ($state, callable $get, callable $set) {
                            $set('location', [
                                'lat' => floatVal($get('lat')),
                                'lng' => floatVal($get('lng')),
                            ]);
                        })
                        ->prefix('Buscar:')
                        ->placeholder('Escribe una dirección o nombre de hotel...')
                        ->isLocation(false)
                        ->reverseGeocode([
                            'city' => '%L',
                            'zip' => '%z',
                            'state' => '%A1',
                            'street' => '%S',
                            'number' => '%N',
                            'name' => '%S2',
                            'country' => '%C',
                        ])
                        ->maxLength(1024)
                        ->geolocate()
                        ->geolocateIcon('heroicon-o-map')
                        ->countries(['es'])
                        ->columnSpanFull(),
                ]),

        ]);

    }

}
