<?php

namespace App\Filament\App\Facturacion\Resources\NoteResource\Tables;

use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['project', 'folder', 'createdBy']))
            ->defaultSort('pinned_at', 'desc')
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
                TextColumn::make('content')
                    ->label('Contenido')
                    ->formatStateUsing(fn (?string $state): string => $state ? \Illuminate\Support\Str::limit(strip_tags($state), 100) : '')
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('folder.name')
                    ->label('Carpeta')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('createdBy.name')
                    ->label('Autor')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('tags')
                    ->label('Etiquetas')
                    ->badge()
                    ->formatStateUsing(fn (?array $state): string => $state ? implode(', ', $state) : '')
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_pinned')
                    ->label('Fijada')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('pinned_at')
                    ->label('Fijada el')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('is_pinned')
                    ->label('Fijadas'),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
