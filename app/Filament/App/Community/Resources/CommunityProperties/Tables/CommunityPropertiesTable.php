<?php

namespace App\Filament\App\Community\Resources\CommunityProperties\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityPropertiesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')->label('Propiedad')->searchable()->sortable(), TextColumn::make('unit_reference')->label('Unidad')->searchable(), TextColumn::make('community.name')->label('Comunidad')->searchable(), 
            TextColumn::make('owners.email')->label('Propietario')->badge(), 
            TextColumn::make('community_documents_count')->label('Documentos'), 
            TextColumn::make('community_tickets_count')->label('Tickets'), IconColumn::make('is_active')->label('Activa')->boolean()])
            ->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload()])
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
