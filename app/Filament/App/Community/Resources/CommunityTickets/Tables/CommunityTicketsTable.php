<?php

namespace App\Filament\App\Community\Resources\CommunityTickets\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class CommunityTicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')->columns([
                TextColumn::make('title')->label('Ticket')->searchable(), TextColumn::make('person.display_name')->label('Propietario')->searchable(), TextColumn::make('property.name')->label('Propiedad'),
                TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('department.name')->label('Departamento')->badge(), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('due_at')->label('Vence')->dateTime('d/m/Y H:i'),
            ])->filters([
                SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), 
                SelectFilter::make('property')->relationship('property', 'name')->searchable()->preload(), 

                SelectFilter::make('priority')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente']), 
                SelectFilter::make('status')->options(['open' => 'Abierto', 'in_progress' => 'En curso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado']),
                ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])->headerActions([

        ]);
    }
}
