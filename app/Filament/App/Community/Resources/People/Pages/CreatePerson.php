<?php

namespace App\Filament\App\Community\Resources\People\Pages;

use App\Filament\App\Community\Resources\People\PersonResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePerson extends CreateRecord
{
    protected static string $resource = PersonResource::class;
}
