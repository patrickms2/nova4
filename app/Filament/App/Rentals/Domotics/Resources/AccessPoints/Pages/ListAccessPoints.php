<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessPoints\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\AccessPointResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccessPoints extends ListRecords
{
    protected static string $resource = AccessPointResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
