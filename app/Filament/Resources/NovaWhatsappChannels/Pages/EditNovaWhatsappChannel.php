<?php

namespace App\Filament\Resources\NovaWhatsappChannels\Pages;

use App\Filament\Resources\NovaWhatsappChannels\NovaWhatsappChannelResource;
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
