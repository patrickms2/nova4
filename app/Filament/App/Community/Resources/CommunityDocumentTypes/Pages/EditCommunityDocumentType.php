<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentTypes\CommunityDocumentTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityDocumentType extends EditRecord
{
    protected static string $resource = CommunityDocumentTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
