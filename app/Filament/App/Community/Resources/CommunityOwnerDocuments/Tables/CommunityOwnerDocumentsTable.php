<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityOwnerDocumentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')->columns([
                TextColumn::make('title')->label('Documento')->searchable(), 
                TextColumn::make('person.email')->label('Propietario')->searchable()->badge(), 
                TextColumn::make('property.name')->label('Propiedad'), 
                TextColumn::make('documentType.name')->label('Tipo')->badge(), 
                TextColumn::make('community.name')->label('Comunidad'), 
                TextColumn::make('expires_at')->label('Caduca')->date(), 
                TextColumn::make('status')->label('Estado')->badge(),
            ])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('documentType')->relationship('documentType', 'name')->searchable()->preload(), SelectFilter::make('status')->options(['active' => 'Activo', 'expired' => 'Caducado', 'archived' => 'Archivado'])])
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
