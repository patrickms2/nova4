<?php

namespace App\Filament\App\Community\Resources\WorkCatalogs\Pages;

use App\Filament\App\Community\Resources\WorkCatalogs\WorkCatalogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkCatalogs extends ListRecords
{
    protected static string $resource = WorkCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
