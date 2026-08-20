<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories\Pages;

use App\Filament\App\NovaHub\Resources\NovaListingCategories\NovaListingCategoryResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateNovaListingCategory extends CreateRecord
{
    protected static string $resource = NovaListingCategoryResource::class;
}
