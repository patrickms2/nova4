<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Pages;

use App\Actions\Community\ImportOwnerDocuments;
use App\Filament\App\Community\Resources\CommunityDocumentImports\CommunityDocumentImportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityDocumentImport extends CreateRecord
{
    protected static string $resource = CommunityDocumentImportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['original_name'] = basename($data['source_path']);
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        app(ImportOwnerDocuments::class)->handle($this->record);
    }
}
