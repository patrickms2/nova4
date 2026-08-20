<?php

namespace App\Filament\App\Facturacion\Resources\ClienteResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ClienteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('codcliente')
                    ->label('Código')
                    ->maxLength(20)
                    ->required(),
                TextInput::make('nombretotal')
                    ->label('Nombre completo')
                    ->maxLength(150)
                    ->columnSpanFull(),
                TextInput::make('nombre')->maxLength(80),
                TextInput::make('apellido')->maxLength(80),
                TextInput::make('dni')->label('DNI/NIF')->maxLength(20),
                TextInput::make('email')->email()->maxLength(80),
                TextInput::make('telefono')->maxLength(20),
                TextInput::make('movil')->maxLength(20),
                TextInput::make('domicilio')->maxLength(255)->columnSpanFull(),
                TextInput::make('poblacion')->maxLength(80),
                TextInput::make('codigopostal')->maxLength(10),
                TextInput::make('pais')->maxLength(60),
                TextInput::make('cuentacorriente')->maxLength(40),
                Textarea::make('observaciones')->columnSpanFull(),
                Toggle::make('domiciliado')->label('Domiciliado'),
            ])
            ->columns(2);
    }
}
