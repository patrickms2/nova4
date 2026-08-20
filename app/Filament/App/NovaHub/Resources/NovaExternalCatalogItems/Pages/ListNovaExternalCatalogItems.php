<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\NovaExternalCatalogItemResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaExternalCatalogItems extends ListRecords
{
    protected static string $resource = NovaExternalCatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
