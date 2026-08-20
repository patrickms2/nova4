<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommunityDocumentTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')->label('Tipo')->searchable()->sortable(), TextColumn::make('code')->label('Código')->badge(), TextColumn::make('community.name')->label('Comunidad')->placeholder('Global'), TextColumn::make('documents_count')->label('Documentos')->counts('documents'), IconColumn::make('requires_expiration')->label('Caduca')->boolean(), IconColumn::make('is_active')->label('Activo')->boolean()])
            ->filters([])
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
