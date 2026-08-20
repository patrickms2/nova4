<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\CommunityOwnerDocumentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityOwnerDocument extends EditRecord
{
    protected static string $resource = CommunityOwnerDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
