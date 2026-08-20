<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\AccessPointResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccessPoint extends EditRecord
{
    protected static string $resource = AccessPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
