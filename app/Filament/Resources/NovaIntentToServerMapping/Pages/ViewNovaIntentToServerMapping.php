<?php

namespace App\Filament\Resources\NovaIntentToServerMapping\Pages;

use App\Filament\Resources\NovaIntentToServerMapping\NovaIntentToServerMappingResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNovaIntentToServerMapping extends ViewRecord
{
    protected static string $resource = NovaIntentToServerMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
