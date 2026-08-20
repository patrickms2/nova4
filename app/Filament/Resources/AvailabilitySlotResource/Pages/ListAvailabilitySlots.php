<?php

declare(strict_types=1);

namespace App\Filament\Resources\AvailabilitySlotResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\AvailabilitySlotResource;

class ListAvailabilitySlots extends ListRecords
{
    protected static string $resource = AvailabilitySlotResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
