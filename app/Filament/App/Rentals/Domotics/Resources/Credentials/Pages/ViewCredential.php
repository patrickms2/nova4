<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Credentials\CredentialResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCredential extends ViewRecord
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
