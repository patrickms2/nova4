<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSyncLogs\NovaIntegrationSyncLogResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNovaIntegrationSyncLog extends CreateRecord
{
    protected static string $resource = NovaIntegrationSyncLogResource::class;
}
