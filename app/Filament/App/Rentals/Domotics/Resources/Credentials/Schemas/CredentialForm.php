<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CredentialForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Credencial')->schema([
                    Select::make('person_id')->label('Persona')->relationship('person', 'display_name')->searchable()->preload()->nullable(),
                    TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                    Select::make('type')->label('Tipo')->options(array_combine(['pin', 'qr', 'rfid', 'nfc', 'mobile', 'biometric', 'external'], ['PIN', 'QR', 'RFID', 'NFC', 'Móvil', 'Biométrica', 'Externa']))->required(),
                    TextInput::make('identifier')->label('Referencia pública')->maxLength(255)->unique(ignoreRecord: true),
                    TextInput::make('secret')->label('Valor secreto')->password()->required(fn (string $operation): bool => $operation === 'create')->afterStateHydrated(fn (TextInput $component) => $component->state(null))->dehydrated(fn (?string $state): bool => filled($state))->helperText('Se cifra al guardar y nunca vuelve a mostrarse.'),
                    Select::make('status')->label('Estado')->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'revoked' => 'Revocada'])->default('active')->required(),
                    DateTimePicker::make('valid_from')->label('Válida desde'),
                    DateTimePicker::make('valid_until')->label('Válida hasta')->afterOrEqual('valid_from'),
                ])->columns(2),
            ]);
    }
}
