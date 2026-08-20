<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Pages;

use App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\NovaExternalCatalogItemResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaExternalCatalogItem extends EditRecord
{
    protected static string $resource = NovaExternalCatalogItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
