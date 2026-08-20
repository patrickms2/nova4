<?php

namespace App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Pages;

use App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\NovaWhatsappChannelResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditNovaWhatsappChannel extends EditRecord
{
    protected static string $resource = NovaWhatsappChannelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
