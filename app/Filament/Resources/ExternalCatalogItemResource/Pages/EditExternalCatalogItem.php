<?php

namespace App\Filament\Resources\ExternalCatalogItemResource\Pages;
use App\Filament\Resources\ExternalCatalogItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExternalCatalogItem extends EditRecord
{
    protected static string $resource = ExternalCatalogItemResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
