<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\CommunityOwnerDocumentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityOwnerDocuments extends ListRecords
{
    protected static string $resource = CommunityOwnerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
