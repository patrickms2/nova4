<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalBookings\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaExternalBookingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('service_name')->label('Servicio')->searchable()->sortable()->weight('bold')->limit(45),
                TextColumn::make('business.name')->label('Cliente Nova')->sortable()->toggleable(),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('booking_status')->label('Reserva')->badge()->sortable(),
                TextColumn::make('payment_status')->label('Pago')->badge()->sortable(),
                TextColumn::make('customer_name')->label('Cliente')->searchable()->limit(35),
                TextColumn::make('total')->label('Total')->money('EUR')->sortable(),
                TextColumn::make('booking_starts_at')->label('Fecha')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Origen')->options([
                    'woo' => 'WooCommerce',
                    'latepoint' => 'LatePoint',
                ]),
                SelectFilter::make('booking_status')->label('Reserva'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('booking_starts_at', 'desc');
    }
}
