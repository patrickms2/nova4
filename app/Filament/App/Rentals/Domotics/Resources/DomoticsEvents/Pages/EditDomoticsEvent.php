<?php

namespace App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages;

use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\DomoticsEventResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditDomoticsEvent extends EditRecord
{
    protected static string $resource = DomoticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
