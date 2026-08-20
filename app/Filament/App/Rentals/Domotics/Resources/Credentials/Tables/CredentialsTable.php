<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Credentials\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CredentialsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('person.display_name')->label('Persona')->searchable()->placeholder('Sin persona'),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('masked_value')->label('Credencial')->state(fn ($record): string => $record->maskedValue())->fontFamily('mono'),
                TextColumn::make('valid_from')->label('Válida desde')->dateTime()->sortable(),
                TextColumn::make('valid_until')->label('Válida hasta')->dateTime()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('access_grants_count')->label('Permisos')->counts('accessGrants'),
            ])
            ->filters([
                SelectFilter::make('type')->options(array_combine(['pin', 'qr', 'rfid', 'nfc', 'mobile', 'biometric', 'external'], ['PIN', 'QR', 'RFID', 'NFC', 'Móvil', 'Biométrica', 'Externa'])),
                SelectFilter::make('status')->options(['active' => 'Activa', 'inactive' => 'Inactiva', 'revoked' => 'Revocada']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
