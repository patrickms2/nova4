<?php

namespace App\Filament\Resources\ExternalOrderResource\Pages;
use App\Filament\Resources\ExternalOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExternalOrder extends EditRecord
{
    protected static string $resource = ExternalOrderResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
