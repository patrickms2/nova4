<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages;

use App\Filament\App\NovaHub\Resources\NovaListingCategories\NovaListingCategoryResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListNovaListingCategories extends ListRecords
{
    protected static string $resource = NovaListingCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva categoría'),
        ];
    }
}
