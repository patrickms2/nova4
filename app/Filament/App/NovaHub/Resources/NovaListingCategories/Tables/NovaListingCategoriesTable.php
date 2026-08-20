<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaListingCategories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NovaListingCategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('slug')
                    ->label('Tipo')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->placeholder('Global')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('tool_id')
                    ->label('Tool MCP')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('keywords')
                    ->label('Keywords')
                    ->state(fn ($record): string => is_array($record->keywords) ? NovaListingCategoriesTable . phpimplode(', ', array_slice($record->keywords, 0, 3)) . (count($record->keywords) > 3 ? '…' : '') : '')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                SelectFilter::make('slug')
                    ->label('Tipo')
                    ->options([
                        'restaurant' => 'Restaurante',
                        'visit' => 'Visita',
                        'hotel' => 'Hotel',
                        'product' => 'Producto',
                        'route' => 'Ruta',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activos')
                    ->falseLabel('Solo inactivos'),

                TernaryFilter::make('global')
                    ->label('Alcance')
                    ->trueLabel('Solo globales')
                    ->falseLabel('Solo por negocio')
                    ->queries(
                        true: fn ($query) => $query->whereNull('nova_business_id'),
                        false: fn ($query) => $query->whereNotNull('nova_business_id'),
                    ),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
