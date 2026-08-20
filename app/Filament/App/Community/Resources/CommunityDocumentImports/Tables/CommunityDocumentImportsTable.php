<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityDocumentImportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')->columns([TextColumn::make('original_name')->label('Archivo')->searchable(), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('documentType.name')->label('Tipo')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('files_found')->label('Encontrados'), TextColumn::make('documents_created')->label('Asignados'), TextColumn::make('unmatched_files')->label('Incidencias')->color('danger'), TextColumn::make('processed_at')->label('Procesado')->dateTime('d/m/Y H:i')])
            ->filters([SelectFilter::make('status')->options(['pending' => 'Pendiente', 'processing' => 'Procesando', 'completed' => 'Completado', 'completed_with_issues' => 'Con incidencias', 'failed' => 'Fallido'])])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
