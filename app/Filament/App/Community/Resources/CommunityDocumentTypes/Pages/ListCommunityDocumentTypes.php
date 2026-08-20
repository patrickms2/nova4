<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentTypes\CommunityDocumentTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityDocumentTypes extends ListRecords
{
    protected static string $resource = CommunityDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
