<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\NovaExternalCatalogItemResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNovaExternalCatalogItem extends ViewRecord
{
    protected static string $resource = NovaExternalCatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
