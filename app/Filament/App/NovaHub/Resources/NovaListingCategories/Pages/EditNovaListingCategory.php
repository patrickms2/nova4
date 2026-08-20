<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages;

use App\Filament\App\NovaHub\Resources\NovaListingCategories\NovaListingCategoryResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

final class EditNovaListingCategory extends EditRecord
{
    protected static string $resource = NovaListingCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
