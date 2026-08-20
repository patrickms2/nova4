<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\CommunityOwnerDocumentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityOwnerDocument extends ViewRecord
{
    protected static string $resource = CommunityOwnerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
