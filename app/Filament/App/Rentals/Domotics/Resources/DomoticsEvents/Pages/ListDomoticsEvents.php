<?php

namespace App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages;

use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\DomoticsEventResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDomoticsEvents extends ListRecords
{
    protected static string $resource = DomoticsEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
