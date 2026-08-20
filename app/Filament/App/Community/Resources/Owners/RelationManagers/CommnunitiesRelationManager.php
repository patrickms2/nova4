<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class CommnunitiesRelationManager extends RelationManager
{
    protected static string $relationship = 'communities';

    protected static ?string $title = 'Comunidades';

    public function table(Table $table): Table
    {
   return $table
            ->columns([TextColumn::make('name')->label('Propiedad')->searchable()->sortable(), TextColumn::make('unit_reference')->label('Unidad')->searchable(), TextColumn::make('community.name')->label('Comunidad')->searchable(), TextColumn::make('people.display_name')->label('Propietarios')->badge(), TextColumn::make('community_documents_count')->label('Documentos'), TextColumn::make('community_tickets_count')->label('Tickets'), IconColumn::make('is_active')->label('Activa')->boolean()])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);    }
}
