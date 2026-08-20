<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People\Pages;

use App\Filament\App\Rentals\Domotics\Resources\People\PersonResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPerson extends ViewRecord
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
