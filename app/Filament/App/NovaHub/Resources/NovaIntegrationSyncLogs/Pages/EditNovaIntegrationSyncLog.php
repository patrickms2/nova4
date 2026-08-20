<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\NovaIntegrationSyncLogResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaIntegrationSyncLog extends EditRecord
{
    protected static string $resource = NovaIntegrationSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
