<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Devices\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Devices\DeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DeviceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
