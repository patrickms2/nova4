<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties\Schemas;

use App\Models\Property;
use App\Models\RentalReservation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PropertyInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la propiedad')
                    ->schema([
                        TextEntry::make('name')->label('Nombre'),
                        TextEntry::make('slug')->label('Slug'),
                        TextEntry::make('address')->label('Dirección'),
                        TextEntry::make('timezone')->label('Zona horaria'),
                        TextEntry::make('owner.email')->label('Propietario'),
                        TextEntry::make('is_active')->label('Activa'),
                    ])
                    ->columns(2),
                Section::make('Personas')->schema([
                    TextEntry::make('people.display_name')->label('Contactos y equipo')->badge(),
                    TextEntry::make('people.pivot.role')->label('Roles')->badge(),
                ])->columns(2),
                Section::make('Rentals')->schema([
                    TextEntry::make('rentalProfile.name')->label('Perfil de alquiler')->placeholder('Sin perfil'),
                    TextEntry::make('reservations_count')->label('Reservas'),
                    TextEntry::make('current_stay')->label('Estancia actual')->state(function (Property $record): string {
                        $reservation = $record->reservations->first(fn (RentalReservation $reservation): bool => $reservation->status === 'confirmed' && $reservation->check_in->lte(today()) && $reservation->check_out->gte(today()));

                        return $reservation?->person?->display_name ?? $reservation?->guest?->fullName() ?? 'Propiedad vacante';
                    }),
                    TextEntry::make('next_arrival')->label('Próxima llegada')->state(function (Property $record): string {
                        $reservation = $record->reservations->first(fn (RentalReservation $reservation): bool => $reservation->status === 'confirmed' && $reservation->check_in->gt(today()));

                        return $reservation ? ($reservation->person?->display_name ?? $reservation->guest?->fullName() ?? 'Huésped').' · '.$reservation->check_in->format('d M Y') : 'Sin próximas llegadas';
                    }),
                ])->columns(2),
                Section::make('Acceso')->schema([
                    TextEntry::make('access_points_count')->label('Puntos de acceso'),
                    TextEntry::make('devices_count')->label('Dispositivos'),
                    TextEntry::make('device_health')->label('Salud')->state(fn (Property $record): string => $record->devices->where('status.value', 'online')->count().'/'.$record->devices->count().' online')->badge()->color(fn (Property $record): string => $record->devices->contains(fn ($device): bool => $device->status?->value === 'offline') ? 'danger' : 'success'),
                    TextEntry::make('active_grants_count')->label('Permisos activos'),
                    TextEntry::make('accessGrants.credentials.name')->label('Credenciales autorizadas')->badge()->limitList(8),
                ])->columns(2),
                Section::make('Domótica')->schema([
                    TextEntry::make('active_automations_count')->label('Automatizaciones activas'),
                    TextEntry::make('domoticsEvents.event_type')->label('Eventos recientes')->badge()->limitList(8),
                ])->columns(2),
            ]);
    }
}
