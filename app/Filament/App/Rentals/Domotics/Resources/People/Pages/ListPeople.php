<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People\Pages;

use App\Filament\App\Rentals\Domotics\Resources\People\PersonResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPeople extends ListRecords
{
    protected static string $resource = PersonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
