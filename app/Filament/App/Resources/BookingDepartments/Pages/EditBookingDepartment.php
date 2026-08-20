<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditBookingDepartment extends EditRecord
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static ?string $title = 'Editar departamento';

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
