<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Devices\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Devices\DeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDevices extends ListRecords
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
