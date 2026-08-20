<?php

namespace App\Filament\App\Community\Resources\WorkCatalogs\Pages;

use App\Filament\App\Community\Resources\WorkCatalogs\WorkCatalogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewWorkCatalog extends ViewRecord
{
    protected static string $resource = WorkCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
