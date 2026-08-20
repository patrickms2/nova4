<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CredentialInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credencial')->schema([
                    TextEntry::make('name')->label('Nombre'),
                    TextEntry::make('person.display_name')->label('Persona')->placeholder('Sin persona'),
                    TextEntry::make('type')->label('Tipo')->badge(),
                    TextEntry::make('masked_value')->label('Credencial')->state(fn ($record): string => $record->maskedValue())->fontFamily('mono'),
                    TextEntry::make('status')->label('Estado')->badge(),
                    TextEntry::make('valid_from')->label('Válida desde')->dateTime(),
                    TextEntry::make('valid_until')->label('Válida hasta')->dateTime(),
                    TextEntry::make('accessGrants.name')->label('Permisos')->badge(),
                    TextEntry::make('accessGrants.accessPoints.name')->label('Puntos alcanzables')->badge(),
                ])->columns(2),
            ]);
    }
}
