<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Pages;

use App\Filament\App\Rentals\Domotics\Resources\AccessGrants\AccessGrantResource;
use App\Filament\App\Rentals\Domotics\Resources\Credentials\CredentialResource;
use App\Filament\App\Rentals\Domotics\Resources\People\PersonResource;
use App\Filament\App\Rentals\Resources\RentalReservationResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewRentalReservation extends ViewRecord
{
    protected static string $resource = RentalReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('table')->label('Vista tabla')->icon('heroicon-o-table-cells')->url(RentalReservationResource::getUrl('index')),
            Action::make('calendar')->label('Calendario')->icon('heroicon-o-calendar-days')->url(RentalReservationResource::getUrl('calendar', ['date' => $this->record->check_in?->toDateString()])),
            Action::make('person')->label('Ver persona')->icon('heroicon-o-user')->visible(fn (): bool => $this->record->person !== null)->url(fn (): ?string => $this->record->person ? PersonResource::getUrl('view', ['record' => $this->record->person]) : null),
            Action::make('credential')->label('Ver credencial')->icon('heroicon-o-key')->visible(fn (): bool => $this->record->accessGrants->flatMap->credentials->isNotEmpty())->url(fn (): ?string => ($credential = $this->record->accessGrants->flatMap->credentials->first()) ? CredentialResource::getUrl('view', ['record' => $credential]) : null),
            Action::make('access')->label('Ver permiso')->icon('heroicon-o-lock-open')->visible(fn (): bool => $this->record->accessGrants->isNotEmpty())->url(fn (): ?string => ($grant = $this->record->accessGrants->first()) ? AccessGrantResource::getUrl('view', ['record' => $grant]) : null),
        ];
    }
}
