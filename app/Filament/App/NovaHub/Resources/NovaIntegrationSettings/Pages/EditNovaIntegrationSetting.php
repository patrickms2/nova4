<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\NovaIntegrationSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaIntegrationSetting extends EditRecord
{
    protected static string $resource = NovaIntegrationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
