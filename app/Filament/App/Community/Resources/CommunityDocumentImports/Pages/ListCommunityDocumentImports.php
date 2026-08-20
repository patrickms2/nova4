<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentImports\CommunityDocumentImportResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityDocumentImports extends ListRecords
{
    protected static string $resource = CommunityDocumentImportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
