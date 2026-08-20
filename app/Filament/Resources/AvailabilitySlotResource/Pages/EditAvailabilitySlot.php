<?php

declare(strict_types=1);

namespace App\Filament\Resources\AvailabilitySlotResource\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\AvailabilitySlotResource;

class EditAvailabilitySlot extends EditRecord
{
    protected static string $resource = AvailabilitySlotResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
