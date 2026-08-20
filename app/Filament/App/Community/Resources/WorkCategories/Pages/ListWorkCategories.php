<?php

namespace App\Filament\App\Community\Resources\WorkCategories\Pages;

use App\Filament\App\Community\Resources\WorkCategories\WorkCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListWorkCategories extends ListRecords
{
    protected static string $resource = WorkCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
