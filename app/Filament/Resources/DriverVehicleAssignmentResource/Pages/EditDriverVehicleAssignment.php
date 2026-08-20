<?php

namespace App\Filament\Resources\DriverVehicleAssignmentResource\Pages;
use App\Filament\Resources\DriverVehicleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDriverVehicleAssignment extends EditRecord
{
    protected static string $resource = DriverVehicleAssignmentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
