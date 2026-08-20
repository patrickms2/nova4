<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAccessGrants extends ListRecords
{
    protected static string $resource = AccessGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
