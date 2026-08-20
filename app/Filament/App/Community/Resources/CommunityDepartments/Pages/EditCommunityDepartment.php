<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments\Pages;

use App\Filament\App\Community\Resources\CommunityDepartments\CommunityDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityDepartment extends EditRecord
{
    protected static string $resource = CommunityDepartmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
