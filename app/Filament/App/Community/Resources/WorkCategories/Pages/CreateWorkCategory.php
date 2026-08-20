<?php

namespace App\Filament\App\Community\Resources\WorkCategories\Pages;

use App\Filament\App\Community\Resources\WorkCategories\WorkCategoryResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkCategory extends CreateRecord
{
    protected static string $resource = WorkCategoryResource::class;
}
