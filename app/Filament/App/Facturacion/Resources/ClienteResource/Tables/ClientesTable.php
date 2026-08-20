<?php

namespace App\Filament\App\Facturacion\Resources\ClienteResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ClientesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codcliente')->label('Código')->sortable()->searchable(),
                TextColumn::make('nombreCorto')->label('Nombre')->searchable()->wrap(),
                TextColumn::make('dni')->label('DNI/NIF')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('telefono'),
                IconColumn::make('domiciliado')->boolean(),
            ])
            ->filters([])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
