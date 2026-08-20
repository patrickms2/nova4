<?php

namespace App\Filament\App\Rentals\Domotics\Resources\AccessGrants\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditAccessGrant extends EditRecord
{
    protected static string $resource = AccessGrantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
