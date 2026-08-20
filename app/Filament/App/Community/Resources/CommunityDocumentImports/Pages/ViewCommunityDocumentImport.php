<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentImports\CommunityDocumentImportResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityDocumentImport extends ViewRecord
{
    protected static string $resource = CommunityDocumentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
