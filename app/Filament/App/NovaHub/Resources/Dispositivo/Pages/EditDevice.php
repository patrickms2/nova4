<?php

namespace App\Filament\App\NovaHub\Resources\Dispositivo\Pages;

use App\Filament\App\NovaHub\Resources\Dispositivo\DispositivoResource;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditDevice extends EditRecord
{
    protected static string $resource = DispositivoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
