<?php

declare(strict_types=1);

namespace App\Filament\Resources\AvailabilitySlotResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\AvailabilitySlotResource;

class CreateAvailabilitySlot extends CreateRecord
{
    protected static string $resource = AvailabilitySlotResource::class;
}
