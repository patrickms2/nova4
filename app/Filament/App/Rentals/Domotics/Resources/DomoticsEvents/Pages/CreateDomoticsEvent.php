<?php

namespace App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages;

use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\DomoticsEventResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDomoticsEvent extends CreateRecord
{
    protected static string $resource = DomoticsEventResource::class;
}
