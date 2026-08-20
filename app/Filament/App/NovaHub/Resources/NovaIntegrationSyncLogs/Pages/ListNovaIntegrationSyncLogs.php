<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\NovaIntegrationSyncLogResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaIntegrationSyncLogs extends ListRecords
{
    protected static string $resource = NovaIntegrationSyncLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
