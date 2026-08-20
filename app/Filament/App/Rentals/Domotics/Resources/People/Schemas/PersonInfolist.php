<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad')->schema([
                    TextEntry::make('display_name')->label('Nombre'),
                    TextEntry::make('email'),
                    TextEntry::make('phone')->label('Teléfono'),
                    TextEntry::make('document_number')->label('Documento'),
                    TextEntry::make('user.email')->label('Cuenta NOVA')->placeholder('Sin cuenta'),
                    TextEntry::make('roles.role')->label('Roles')->badge(),
                ])->columns(2),
                Section::make('Propiedades y estancias')->schema([
                    TextEntry::make('properties.name')->label('Propiedades')->badge(),
                    TextEntry::make('reservations.reference_code')->label('Reservas')->badge(),
                ])->columns(2),
                Section::make('Acceso')->schema([
                    TextEntry::make('credentials.name')->label('Credenciales')->badge(),
                    TextEntry::make('accessGrants.name')->label('Permisos')->badge(),
                    TextEntry::make('accessGrants.accessPoints.name')->label('Puntos autorizados')->badge(),
                    TextEntry::make('accessGrants.domoticsEvents.event_type')->label('Actividad reciente')->badge()->limitList(8),
                ])->columns(2),
            ]);
    }
}
