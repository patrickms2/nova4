<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAccessGrant extends ViewRecord
{
    protected static string $resource = AccessGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
