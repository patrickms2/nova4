<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Devices\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Devices\DeviceResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDevice extends ViewRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
