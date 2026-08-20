<?php

namespace App\Filament\App\Facturacion\Resources\FormaCobroResource\Schemas;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class FormaCobroForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('codigo')
                    ->label('Código')
                    ->maxLength(50)
                    ->nullable(),
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->maxLength(150)
                    ->required()
                    ->columnSpanFull(),
                Textarea::make('descripcion')
                    ->label('Descripción')
                    ->nullable()
                    ->columnSpanFull(),
                TextInput::make('orden')
                    ->label('Orden')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Toggle::make('activa')
                    ->label('Activa')
                    ->default(true),
            ])
            ->columns(2);
    }
}
