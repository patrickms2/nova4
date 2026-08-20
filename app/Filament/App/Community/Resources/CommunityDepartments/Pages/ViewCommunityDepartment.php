<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments\Pages;

use App\Filament\App\Community\Resources\CommunityDepartments\CommunityDepartmentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityDepartment extends ViewRecord
{
    protected static string $resource = CommunityDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
