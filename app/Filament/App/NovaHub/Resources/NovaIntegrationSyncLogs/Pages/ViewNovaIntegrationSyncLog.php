<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\NovaIntegrationSyncLogResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewNovaIntegrationSyncLog extends ViewRecord
{
    protected static string $resource = NovaIntegrationSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
