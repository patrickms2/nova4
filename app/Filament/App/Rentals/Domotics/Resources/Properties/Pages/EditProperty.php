<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Properties\PropertyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProperty extends EditRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
