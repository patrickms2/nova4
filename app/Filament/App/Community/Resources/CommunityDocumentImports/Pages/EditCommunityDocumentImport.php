<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentImports\CommunityDocumentImportResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityDocumentImport extends EditRecord
{
    protected static string $resource = CommunityDocumentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
