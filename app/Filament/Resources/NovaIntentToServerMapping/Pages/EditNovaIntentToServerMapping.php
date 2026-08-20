<?php

namespace App\Filament\Resources\NovaIntentToServerMapping\Pages;

use App\Filament\Resources\NovaIntentToServerMapping\NovaIntentToServerMappingResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaIntentToServerMapping extends EditRecord
{
    protected static string $resource = NovaIntentToServerMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
