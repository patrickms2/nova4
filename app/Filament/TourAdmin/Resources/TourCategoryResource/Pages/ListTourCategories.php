<?php

namespace App\Filament\TourAdmin\Resources\TourCategoryResource\Pages;

use App\Filament\TourAdmin\Resources\TourCategoryResource;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListTourCategories extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = TourCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
