<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Properties\PropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProperties extends ListRecords
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
