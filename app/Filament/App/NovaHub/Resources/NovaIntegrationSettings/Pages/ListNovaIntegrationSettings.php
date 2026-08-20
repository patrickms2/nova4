<?php

namespace App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\Pages;

use App\Filament\App\NovaHub\Resources\NovaIntegrationSettings\NovaIntegrationSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaIntegrationSettings extends ListRecords
{
    protected static string $resource = NovaIntegrationSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
