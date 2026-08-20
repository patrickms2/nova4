<?php

namespace App\Filament\App\Facturacion\Resources\ProjectResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['parent', 'category', 'createdBy']))
            ->defaultSort('start_date', 'desc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('parent.name')
                    ->label('Proyecto padre')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'planning' => 'info',
                        'on_hold' => 'warning',
                        'completed' => 'primary',
                        'archived' => 'gray',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('phase')
                    ->label('Fase')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'planning' => 'gray',
                        'development' => 'warning',
                        'testing' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('start_date')
                    ->label('Inicio')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('end_date')
                    ->label('Fin')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('is_public')
                    ->label('Público')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'planning' => 'Planificación',
                        'active' => 'Activo',
                        'archived' => 'Archivado',
                        'completed' => 'Completado',
                    ]),
                SelectFilter::make('phase')
                    ->options([
                        'planning' => 'Planificación',
                        'development' => 'Desarrollo',
                        'completed' => 'Completado',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
