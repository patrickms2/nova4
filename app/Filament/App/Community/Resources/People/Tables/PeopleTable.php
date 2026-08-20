<?php

namespace App\Filament\App\Community\Resources\People\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PeopleTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('display_name')->label('Usuario / persona')->searchable()->sortable(), TextColumn::make('document_number')->label('Documento')->searchable(), TextColumn::make('email')->searchable(), TextColumn::make('communities.name')->label('Comunidades')->badge(), TextColumn::make('properties_count')->label('Propiedades'), TextColumn::make('community_documents_count')->label('Documentos'), TextColumn::make('community_tickets_count')->label('Tickets')])
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
