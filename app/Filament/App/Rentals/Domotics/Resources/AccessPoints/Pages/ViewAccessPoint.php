<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\AccessPointResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccessPoint extends ViewRecord
{
    protected static string $resource = AccessPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
