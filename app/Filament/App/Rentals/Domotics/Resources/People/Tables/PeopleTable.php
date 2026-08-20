<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->label('Persona')->searchable()->sortable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('phone')->label('Teléfono')->searchable(),
                TextColumn::make('roles.role')->label('Roles')->badge(),
                TextColumn::make('properties_count')->label('Propiedades')->counts('properties'),
                TextColumn::make('reservations_count')->label('Reservas')->counts('reservations'),
                TextColumn::make('credentials_count')->label('Credenciales')->counts('credentials'),
            ])
            ->filters([
                //
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
