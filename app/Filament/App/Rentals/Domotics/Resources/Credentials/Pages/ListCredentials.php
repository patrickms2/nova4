<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Credentials\CredentialResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCredentials extends ListRecords
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
