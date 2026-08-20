<?php

namespace App\Filament\App\Facturacion\Resources\TaskResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use App\Models\Project;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TasksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['project', 'assignedTo', 'taskCategory']))
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->badge(),
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('assignedTo.name')
                    ->label('Asignado')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('taskCategory.name')
                    ->label('Categoría')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'in_progress' => 'info',
                        'completed', 'done' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'high', 'critical' => 'danger',
                        'medium' => 'warning',
                        'low' => 'success',
                        default => 'gray',
                    })
                    ->sortable()
                    ->searchable(),
                TextColumn::make('type')
                    ->label('Tipo')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('due_date')
                    ->label('Fecha límite')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('is_completed')
                    ->label('Completada')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'in_progress' => 'En progreso',
                        'completed' => 'Completada',
                    ]),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                    ]),
                SelectFilter::make('project_id')
                    ->label('Proyecto')
                    ->options(fn (): array => Project::orderBy('name')->pluck('name', 'id')->all())
                    ->searchable(),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
