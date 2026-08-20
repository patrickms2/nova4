<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\CommunityOwnerDocumentResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityOwnerDocument extends CreateRecord
{
    protected static string $resource = CommunityOwnerDocumentResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return [...$data, 'uploaded_by' => auth()->id(), 'type' => 'other'];
    }
}
