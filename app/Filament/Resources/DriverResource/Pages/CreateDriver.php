<?php

namespace App\Filament\Resources\DriverResource\Pages;
use App\Filament\Resources\DriverResource;
use Filament\Resources\Pages\CreateRecord;
use TarfinLabs\LaravelSpatial\Types\Point;
class CreateDriver extends CreateRecord
{
    protected static string $resource = DriverResource::class;
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
    protected function mutateFormDataBeforeCreate(array $data): array
{
        // Ensure lat/lng are available
        if (isset($data['latitude'], $data['longitude'])) {
            $data['current_location'] = new Point(
                $data['latitude'],
                $data['longitude'],
                4326 // WGS84
            );
        }
        unset($data['latitude'], $data['longitude']);
        return $data;
}
}
