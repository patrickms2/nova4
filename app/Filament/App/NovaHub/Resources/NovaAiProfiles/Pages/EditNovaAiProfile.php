<?php

namespace App\Filament\App\NovaHub\Resources\NovaAiProfiles\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiProfiles\NovaAiProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaAiProfile extends EditRecord
{
    protected static string $resource = NovaAiProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
