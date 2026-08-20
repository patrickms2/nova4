<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\NovaIntentRules\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class NovaIntentRulesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('intent_key')
                    ->label('Intent')
                    ->badge()
                    ->sortable()
                    ->searchable(),

                TextColumn::make('rule_type')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'include' => 'success',
                        'exclude' => 'danger',
                        'boost' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('business.name')
                    ->label('Negocio')
                    ->placeholder('Global')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('keywords')
                    ->label('Keywords')
                    ->state(fn ($record): string => is_array($record->keywords) ? NovaIntentRulesTable . phpimplode(', ', array_slice($record->keywords, 0, 4)) . (count($record->keywords) > 4 ? '…' : '') : '')
                    ->wrap()
                    ->toggleable(),

                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),

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
                SelectFilter::make('intent_key')
                    ->label('Intent')
                    ->options([
                        'taxi' => 'Taxi',
                        'restaurant' => 'Restaurante',
                        'visit' => 'Visita',
                        'hotel' => 'Hotel',
                        'product' => 'Producto',
                        'route' => 'Ruta',
                        'booking' => 'Reserva',
                        'price' => 'Precio',
                        'availability' => 'Disponibilidad',
                        'recommendation' => 'Recomendación',
                        'info' => 'Información',
                        'generic' => 'Genérico',
                    ]),

                SelectFilter::make('rule_type')
                    ->label('Tipo')
                    ->options([
                        'include' => 'Include',
                        'exclude' => 'Exclude',
                        'boost' => 'Boost',
                    ]),

                TernaryFilter::make('is_active')
                    ->label('Estado')
                    ->trueLabel('Solo activas')
                    ->falseLabel('Solo inactivas'),

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
