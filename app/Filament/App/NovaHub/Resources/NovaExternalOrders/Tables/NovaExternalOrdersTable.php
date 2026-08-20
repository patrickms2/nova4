<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalOrders\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaExternalOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('external_increment_id')->label('Pedido')->searchable()->sortable()->weight('bold'),
                TextColumn::make('business.name')->label('Cliente Nova')->sortable()->toggleable(),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('payment_status')->label('Pago')->badge()->sortable(),
                TextColumn::make('customer_email')->label('Email')->searchable()->limit(35),
                TextColumn::make('grand_total')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('ordered_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Origen')->options([
                    'woo' => 'WooCommerce',
                    'magento' => 'Magento',
                ]),
                SelectFilter::make('payment_status')->label('Pago')->options([
                    'paid' => 'Pagado',
                    'pending' => 'Pendiente',
                    'refunded' => 'Devuelto',
                    'failed' => 'Fallido',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('ordered_at', 'desc');
    }
}
