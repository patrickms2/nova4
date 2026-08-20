<?php

namespace App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Pages;

use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\NovaWhatsappChannelResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNovaWhatsappChannels extends ListRecords
{
    protected static string $resource = NovaWhatsappChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
