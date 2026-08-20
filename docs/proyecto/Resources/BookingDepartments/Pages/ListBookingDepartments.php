<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBookingDepartments extends ListRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static ?string $title = 'Departamentos';

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo departamento')
                ->icon('heroicon-s-plus'),
        ];
    }
}
