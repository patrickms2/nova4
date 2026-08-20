<?php

namespace App\Filament\App\Facturacion\Resources\FormaCobroResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class FormasCobroTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->sortable()->searchable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->wrap(),
                TextColumn::make('descripcion')->label('Descripción')->wrap()->limit(50),
                TextColumn::make('orden')->label('Orden')->sortable(),
                IconColumn::make('activa')->label('Activa')->boolean(),
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
