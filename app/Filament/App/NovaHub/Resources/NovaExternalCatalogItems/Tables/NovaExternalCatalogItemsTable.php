<?php

namespace App\Filament\App\NovaHub\Resources\NovaExternalCatalogItems\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaExternalCatalogItemsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable()->weight('bold')->limit(45),
                TextColumn::make('business.name')->label('Cliente')->sortable()->toggleable(),
                TextColumn::make('source')->label('Origen')->badge()->sortable(),
                TextColumn::make('type')->label('Tipo')->badge()->sortable(),
                TextColumn::make('sku')->label('SKU')->searchable()->toggleable(),
                TextColumn::make('price')->label('Precio')->money('EUR')->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('last_synced_at')->label('Sync')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('source')->label('Origen')->options([
                    'taxilanz_wp' => 'Taxilanz WP',
                    'magento' => 'Magento',
                    'woo' => 'WooCommerce',
                    'latepoint' => 'LatePoint',
                ]),
                SelectFilter::make('type')->label('Tipo'),
            ])
            ->recordActions([
                ViewAction::make(),
            ])
            ->defaultSort('last_synced_at', 'desc');
    }
}
