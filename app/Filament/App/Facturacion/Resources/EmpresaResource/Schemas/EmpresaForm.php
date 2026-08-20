<?php

namespace App\Filament\App\Facturacion\Resources\EmpresaResource\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class EmpresaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->schema([
                TextInput::make('codeempresa')
                    ->label('Código')
                    ->numeric()
                    ->required(),
                TextInput::make('empresa')
                    ->label('Razón social')
                    ->maxLength(150)
                    ->required()
                    ->columnSpanFull(),
                TextInput::make('nif')->maxLength(20)->label('NIF'),
                TextInput::make('email')->email()->maxLength(80),
                TextInput::make('telefono')->maxLength(20),
                TextInput::make('web')->maxLength(80),
                TextInput::make('direccion')->maxLength(255)->columnSpanFull(),
                TextInput::make('poblacion')->maxLength(150),
                TextInput::make('codigopostal')->maxLength(15),
                TextInput::make('cuentacorriente')->maxLength(40),
                TextInput::make('porcentajeexplotacion')
                    ->numeric()
                    ->suffix('%')
                    ->helperText('Porcentaje de explotación en caso de contratos compartidos.'),
                FileUpload::make('logoempresa')
                    ->label('Logo empresa')
                    ->disk('public')
                    ->directory('empresas/logos')
                    ->image()
                    ->imagePreviewHeight('100')
                    ->columnSpanFull()->maxSize(2048),
                Textarea::make('observaciones')->columnSpanFull(),
            ])
            ->columns(2);
    }
}
