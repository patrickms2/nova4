<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Pages;

use App\Filament\App\Community\Resources\CommunityProperties\CommunityPropertyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCommunityProperty extends ViewRecord
{
    protected static string $resource = CommunityPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
