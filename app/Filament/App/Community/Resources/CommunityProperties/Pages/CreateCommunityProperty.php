<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Pages;

use App\Filament\App\Community\Resources\CommunityProperties\CommunityPropertyResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCommunityProperty extends CreateRecord
{
    protected static string $resource = CommunityPropertyResource::class;
}
