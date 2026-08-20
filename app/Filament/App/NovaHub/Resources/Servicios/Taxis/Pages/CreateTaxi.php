<?php

namespace App\Filament\App\NovaHub\Resources\Servicios\Taxis\Pages;

use App\Filament\App\NovaHub\Resources\Servicios\Taxis\TaxiResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTaxi extends CreateRecord
{
    protected static string $resource = TaxiResource::class;
}
