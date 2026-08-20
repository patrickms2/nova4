<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOwner extends ViewRecord
{
    protected static string $resource = OwnerResource::class;

    public function getTitle(): string
    {
        return $this->record->display_name;
    }

    protected function getHeaderActions(): array
    {
        return [EditAction::make()];
    }
}
