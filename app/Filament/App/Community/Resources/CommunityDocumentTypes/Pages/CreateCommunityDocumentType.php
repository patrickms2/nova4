<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages;

use App\Filament\App\Community\Resources\CommunityDocumentTypes\CommunityDocumentTypeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityDocumentType extends CreateRecord
{
    protected static string $resource = CommunityDocumentTypeResource::class;
}
