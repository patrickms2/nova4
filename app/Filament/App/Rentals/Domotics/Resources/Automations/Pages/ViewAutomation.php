<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Automations\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Automations\AutomationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomation extends ViewRecord
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
