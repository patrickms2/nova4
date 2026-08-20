<?php

namespace App\Filament\App\Rentals\Resources\RentalReservationResource\Schemas;

use App\Models\RentalReservation;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class RentalReservationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Reserva')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('property.name')->label('Propiedad canónica')->placeholder(fn (RentalReservation $record): string => $record->rentalProperty?->name ?? 'Sin propiedad'),
                        TextEntry::make('person.display_name')->label('Persona')->placeholder(fn (RentalReservation $record): string => $record->guest?->fullName() ?? 'Sin huésped'),
                        TextEntry::make('channel')->label('Canal')->badge(),
                        TextEntry::make('reference_code')->label('Referencia'),
                        TextEntry::make('check_in')->label('Entrada')->date('d M Y'),
                        TextEntry::make('check_out')->label('Salida')->date('d M Y'),
                        TextEntry::make('nights')->label('Noches')->state(fn (RentalReservation $record): int => $record->nights()),
                        TextEntry::make('adults')->label('Adultos'),
                        TextEntry::make('children')->label('Niños'),
                        TextEntry::make('amount')->label('Total pagado')->money('EUR'),
                    ]),
                Section::make('Huésped e identidad')->columns(2)->schema([
                    TextEntry::make('guest_name')->label('Perfil de huésped')->state(fn (RentalReservation $record): string => $record->guest?->fullName() ?? 'Sin perfil de huésped'),
                    TextEntry::make('person.display_name')->label('Persona canónica')->placeholder('Sin persona vinculada'),
                    TextEntry::make('guest.email')->label('Email')->placeholder(fn (RentalReservation $record): string => $record->person?->email ?? '—'),
                    TextEntry::make('guest.phone')->label('Teléfono')->placeholder(fn (RentalReservation $record): string => $record->person?->phone ?? '—'),
                    TextEntry::make('guest.document_number')->label('Documento')->placeholder(fn (RentalReservation $record): string => $record->person?->document_number ?? '—'),
                    TextEntry::make('person.roles.role')->label('Roles')->badge()->placeholder('Guest'),
                ]),
                Section::make('Liquidación estimada')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('settlement.accommodation_amount')->label('Alojamiento')->money('EUR')->default(0),
                        TextEntry::make('settlement.services_amount')->label('Tasas a huésped')->money('EUR')->default(0),
                        TextEntry::make('settlement.channel_commission_amount')->label('Comisión canal')->money('EUR')->default(0),
                        TextEntry::make('settlement.commissionable_base')->label('Base comisionable')->money('EUR')->default(0),
                        TextEntry::make('settlement.manager_commission_amount')->label('Comisión gestor')->money('EUR')->default(0),
                        TextEntry::make('settlement.estimated_net')->label('Neto estimado')->money('EUR')->default(0),
                    ]),
                Section::make('Comisiones y estado')
                    ->columns(2)
                    ->schema([
                        TextEntry::make('channel_commission')->label('Comisión canal (reserva)')->money('EUR'),
                        TextEntry::make('management_commission')->label('Comisión gestor (reserva)')->money('EUR'),
                        TextEntry::make('payout')->label('Payout real')->money('EUR'),
                        TextEntry::make('status')->label('Estado')->badge(),
                    ]),
                Section::make('Preparación de acceso')->columns(2)->schema([
                    TextEntry::make('person_ready')->label('Persona vinculada')->state(fn (RentalReservation $record): string => $record->person_id ? 'Preparada' : 'Pendiente')->badge()->color(fn (RentalReservation $record): string => $record->person_id ? 'success' : 'warning'),
                    TextEntry::make('grant_ready')->label('Permiso de acceso')->state(fn (RentalReservation $record): string => $record->accessGrants->isNotEmpty() ? 'Creado' : 'Pendiente')->badge()->color(fn (RentalReservation $record): string => $record->accessGrants->isNotEmpty() ? 'success' : 'warning'),
                    TextEntry::make('credential_ready')->label('Credencial')->state(fn (RentalReservation $record): string => $record->accessGrants->contains(fn ($grant): bool => $grant->credentials->isNotEmpty() || filled($grant->pin)) ? 'Preparada' : 'Pendiente')->badge()->color(fn (RentalReservation $record): string => $record->accessGrants->contains(fn ($grant): bool => $grant->credentials->isNotEmpty() || filled($grant->pin)) ? 'success' : 'warning'),
                    TextEntry::make('points_ready')->label('Puntos asignados')->state(fn (RentalReservation $record): string => $record->accessGrants->contains(fn ($grant): bool => $grant->accessPoints->isNotEmpty()) ? 'Preparados' : 'Pendientes')->badge()->color(fn (RentalReservation $record): string => $record->accessGrants->contains(fn ($grant): bool => $grant->accessPoints->isNotEmpty()) ? 'success' : 'warning'),
                    TextEntry::make('accessGrants.accessPoints.name')->label('Acceso autorizado')->badge()->placeholder('Sin puntos autorizados')->columnSpanFull(),
                    TextEntry::make('credential_names')->label('Credenciales')->state(fn (RentalReservation $record): array => $record->accessGrants->flatMap->credentials->map(fn ($credential): string => $credential->name.' · '.$credential->maskedValue())->unique()->values()->all())->badge()->placeholder('Sin credenciales')->columnSpanFull(),
                ]),
                Section::make('Relaciones')->columns(3)->schema([
                    TextEntry::make('payments_count')->label('Pagos'),
                    TextEntry::make('documents_count')->label('Documentos'),
                    TextEntry::make('incidents_count')->label('Incidencias'),
                    TextEntry::make('components_count')->label('Componentes'),
                    TextEntry::make('timeline_events_count')->label('Actividad'),
                    TextEntry::make('access_grants_count')->label('Permisos'),
                ]),
            ]);
    }
}
