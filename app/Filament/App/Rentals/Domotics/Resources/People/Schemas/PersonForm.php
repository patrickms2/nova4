<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identidad')->schema([
                    TextInput::make('first_name')->label('Nombre')->required(),
                    TextInput::make('last_name')->label('Apellidos'),
                    TextInput::make('display_name')->label('Nombre visible')->required(),
                    TextInput::make('email')->email()->unique(ignoreRecord: true),
                    TextInput::make('phone')->tel()->label('Teléfono'),
                    TextInput::make('document_type')->label('Tipo de documento'),
                    TextInput::make('document_number')->label('Documento'),
                    Select::make('user_id')->label('Cuenta NOVA')->relationship('user', 'email')->searchable()->preload()->unique(ignoreRecord: true),
                ])->columns(2),
                Section::make('Roles')->schema([
                    Repeater::make('roles')->relationship()->schema([
                        Select::make('role')->options(array_combine(['owner', 'guest', 'client', 'staff', 'employee', 'professional', 'property_manager', 'maintenance', 'cleaner', 'gardener', 'provider', 'administrator'], ['Propietario', 'Huésped', 'Cliente', 'Personal', 'Empleado', 'Profesional', 'Gestor', 'Mantenimiento', 'Limpieza', 'Jardinería', 'Proveedor', 'Administrador']))->searchable()->required(),
                    ])->addActionLabel('Añadir rol')->columns(1),
                ]),
            ]);
    }
}
