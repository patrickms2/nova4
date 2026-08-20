<?php

namespace App\Filament\App\Community\Resources\WorkCatalogs\Pages;

use App\Filament\App\Community\Resources\WorkCatalogs\WorkCatalogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkCatalog extends EditRecord
{
    protected static string $resource = WorkCatalogResource::class;

    protected function getHeaderActions(): array
    {
        return [ViewAction::make(), DeleteAction::make()];
    }
}
