<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Automations\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Automations\AutomationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAutomations extends ListRecords
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
