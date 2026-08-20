<?php

namespace App\Filament\App\NovaHub\Resources\Hoteles\Pages;

use App\Filament\App\NovaHub\Resources\Hoteles\HotelesResource;
use Filament\Resources\Pages\CreateRecord;

class CreateHoteles extends CreateRecord
{
    protected static string $resource = HotelesResource::class;

    protected function getHeaderActions(): array
    {
        return [

        ];
    }
}
