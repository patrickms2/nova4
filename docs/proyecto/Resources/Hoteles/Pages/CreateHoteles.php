<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Hoteles\Pages;

use App\Filament\App\Resources\Hoteles\HotelesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoteles extends CreateRecord
{
    protected static string $resource = HotelesResource::class;

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'Hotel creado correctamente';
    }
}
