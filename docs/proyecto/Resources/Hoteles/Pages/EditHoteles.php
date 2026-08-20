<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Hoteles\Pages;

use App\Filament\App\Resources\Hoteles\HotelesResource;
use Filament\Resources\Pages\EditRecord;

class EditHoteles extends EditRecord
{
    protected static string $resource = HotelesResource::class;

    protected function getSavedNotificationTitle(): ?string
    {
        return 'Hotel actualizado correctamente';
    }

    protected function getDeletedNotificationTitle(): ?string
    {
        return 'Hotel eliminado correctamente';
    }
}
