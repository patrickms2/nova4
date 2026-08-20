<?php

namespace App\Filament\Resources\ExternalSourceResource\Pages;
use App\Filament\Resources\ExternalSourceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditExternalSource extends EditRecord
{
    protected static string $resource = ExternalSourceResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
