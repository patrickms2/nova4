<?php

namespace App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages;

use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\DomoticsEventResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewDomoticsEvent extends ViewRecord
{
    protected static string $resource = DomoticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
