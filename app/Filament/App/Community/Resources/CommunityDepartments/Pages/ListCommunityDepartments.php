<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments\Pages;

use App\Filament\App\Community\Resources\CommunityDepartments\CommunityDepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityDepartments extends ListRecords
{
    protected static string $resource = CommunityDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
