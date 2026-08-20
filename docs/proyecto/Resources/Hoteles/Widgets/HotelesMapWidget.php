<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Hoteles\Widgets;

use App\Models\Taxi\UsuarioDireccion;
use Cheesegrits\FilamentGoogleMaps\Fields\Map;
use Cheesegrits\FilamentGoogleMaps\Widgets\MapWidget;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class HotelesMapWidget extends MapWidget
{
    protected static ?string $heading = 'Mapa de Hoteles';

    protected static ?int $sort = 1;

    protected ?string $pollingInterval = null;

    protected static ?bool $clustering = true;

    protected static ?bool $fitToBounds = true;

    protected static ?int $zoom = 10;

    protected static ?string $minHeight = '500px';

    // protected static ?string $markerAction = 'editLocation';

    protected int|string|array $columnSpan = 'full';

    public function getConfig(): array
    {
        $config = parent::getConfig();
        $config['mapConfig']['mapId'] = null;

        return $config;
    }

    protected function getData(): array
    {
        $hotels = UsuarioDireccion::query()
            ->whereNotNull('lat')
            ->whereNotNull('lng')
            ->where('lat', '!=', 0)
            ->where('lng', '!=', 0)
            ->get();

        $data = [];

        foreach ($hotels as $hotel) {
            $data[] = [
                'location' => [
                    'lat' => (float)$hotel->lat,
                    'lng' => (float)$hotel->lng,
                ],
                'label' => $hotel->name ?? $hotel->address ?? 'Hotel #' . $hotel->id,
                'id' => $hotel->id,
            ];
        }

        return $data;
    }

    public function editLocationAction(): Action
    {
        $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];
        $defaultBoundaries = ['north' => $defaultCenter['lat'] + 0.1, 'south' => $defaultCenter['lat'] - 0.1, 'east' => $defaultCenter['lng'] + 0.1, 'west' => $defaultCenter['lng'] - 0.1];

        return Action::make('editLocation')
            ->label('Editar ubicación')
            ->icon('heroicon-o-map-pin')
            ->fillForm(function (array $arguments): array {
                $hotel = UsuarioDireccion::find($arguments['model_id']);

                if (!$hotel) {
                    return [];
                }

                return [
                    'name' => $hotel->name ?? $hotel->address ?? 'Hotel #' . $hotel->id,
                    'location' => [
                        'lat' => (float)$hotel->lat,
                        'lng' => (float)$hotel->lng,
                    ],
                ];
            })
            ->form([
                \Filament\Forms\Components\TextInput::make('name')
                    ->label('Hotel')
                    ->disabled(),
                Map::make('location')
                    ->label('Arrastra el marcador para mover')
                    ->defaultLocation($defaultCenter)
                    ->defaultZoom(14)
                    ->draggable(true)
                    ->clickable(true)
                    ->height('400px')
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, array $arguments): void {
                $hotel = UsuarioDireccion::find($arguments['model_id']);

                if (!$hotel) {
                    return;
                }

                $hotel->update([
                    'lat' => $data['location']['lat'],
                    'lng' => $data['location']['lng'],
                ]);

                $this->cachedData = null;

                Notification::make()
                    ->success()
                    ->title('Ubicación actualizada')
                    ->body($hotel->name . ': ' . round($data['location']['lat'], 6) . ', ' . round($data['location']['lng'], 6))
                    ->send();
            });
    }
}
