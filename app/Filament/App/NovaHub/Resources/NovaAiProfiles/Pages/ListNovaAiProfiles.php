<?php

namespace App\Filament\App\NovaHub\Resources\NovaAiProfiles\Pages;

use App\Filament\App\NovaHub\Resources\NovaAiProfiles\NovaAiProfileResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaAiProfiles extends ListRecords
{
    protected static string $resource = NovaAiProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
