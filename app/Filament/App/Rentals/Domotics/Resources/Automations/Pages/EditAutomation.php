<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Automations\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Automations\AutomationResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAutomation extends EditRecord
{
    protected static string $resource = AutomationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
