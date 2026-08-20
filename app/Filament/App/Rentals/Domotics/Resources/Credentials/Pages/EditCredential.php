<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Pages;

use App\Filament\App\Rentals\Domotics\Resources\Credentials\CredentialResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCredential extends EditRecord
{
    protected static string $resource = CredentialResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
