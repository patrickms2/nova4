<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use App\Filament\App\Rentals\Domotics\Resources\AccessPoints\AccessPointResource;
use App\Filament\App\Rentals\Domotics\Resources\Devices\DeviceResource;
use App\Filament\App\Rentals\Domotics\Resources\Properties\PropertyResource;
use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProperty extends ViewRecord
{
    protected static string $resource = PropertyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('reservations')->label('Reservas')->icon('heroicon-o-calendar-days')->url(fn (): string => RentalReservationResource::getUrl('index', ['tableFilters' => ['property' => ['value' => $this->record->getKey()]]])),
            Action::make('access')->label('Permisos de acceso')->icon('heroicon-o-key')->url(fn (): string => AccessGrantResource::getUrl('index', ['tableFilters' => ['property' => ['value' => $this->record->getKey()]]])),
            Action::make('accessPoints')->label('Puntos de acceso')->icon('heroicon-o-lock-open')->url(fn (): string => AccessPointResource::getUrl('index')),
            Action::make('devices')->label('Dispositivos')->icon('heroicon-o-cpu-chip')->url(fn (): string => DeviceResource::getUrl('index')),
            EditAction::make(),
        ];
    }
}
