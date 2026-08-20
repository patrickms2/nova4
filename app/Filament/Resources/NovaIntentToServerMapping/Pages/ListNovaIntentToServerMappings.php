<?php

namespace App\Filament\Resources\NovaIntentToServerMapping\Pages;

use App\Filament\Resources\NovaIntentToServerMapping\NovaIntentToServerMappingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaIntentToServerMappings extends ListRecords
{
    protected static string $resource = NovaIntentToServerMappingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
