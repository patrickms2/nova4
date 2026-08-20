<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaCrossSellingRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NovaCrossSellingRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('fromBusiness.name')
                    ->label('Negocio origen')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('trigger_intent')
                    ->label('Intent')
                    ->badge()
                    ->sortable(),

                TextColumn::make('toBusiness.name')
                    ->label('Negocio sugerido')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('message')
                    ->label('Mensaje')
                    ->limit(40)
                    ->toggleable(),

                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->sortable()
                    ->toggleable(),
            ])
            ->defaultSort('priority')
            ->filters([
                SelectFilter::make('trigger_intent')
                    ->label('Intent')
                    ->options([
                        'taxi' => 'Taxi',
                        'restaurant' => 'Restaurante',
                        'visit' => 'Visita',
                        'hotel' => 'Hotel',
                        'product' => 'Producto',
                        'route' => 'Ruta',
                        'generic' => 'Genérico',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),
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
