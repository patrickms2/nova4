<?php

namespace App\Filament\Resources\DriverVehicleAssignmentResource\Pages;
use App\Filament\Resources\DriverVehicleAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDriverVehicleAssignments extends ListRecords
{
    protected static string $resource = DriverVehicleAssignmentResource::class;
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
