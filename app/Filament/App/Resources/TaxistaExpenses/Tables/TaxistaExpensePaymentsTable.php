<?php

namespace App\Filament\App\Resources\TaxistaExpenses\Tables;

use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TaxistaExpensePaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('amount')
                    ->label('Importe pago')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge(),

                TextColumn::make('payment_date')
                    ->label('Fecha pago')
                    ->date('m / d / Y')
                    ->sortable(),

                TextColumn::make('reference')
                    ->label('Referencia')
                    ->toggleable(),
            ])
            ->defaultSort('payment_date', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
