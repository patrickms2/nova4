<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Pages;

use App\Filament\App\Community\Resources\CommunityProperties\CommunityPropertyResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditCommunityProperty extends EditRecord
{
    protected static string $resource = CommunityPropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
