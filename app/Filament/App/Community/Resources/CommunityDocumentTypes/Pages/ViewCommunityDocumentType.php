<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentTypes\CommunityDocumentTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityDocumentType extends ViewRecord
{
    protected static string $resource = CommunityDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
