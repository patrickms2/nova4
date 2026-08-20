<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Pages;

use App\Filament\App\Community\Resources\CommunityProperties\CommunityPropertyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommunityProperties extends ListRecords
{
    protected static string $resource = CommunityPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
