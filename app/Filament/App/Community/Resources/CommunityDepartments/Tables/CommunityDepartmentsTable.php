<?php

namespace App\Filament\App\Community\Resources\CommunityDepartments\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CommunityDepartmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([TextColumn::make('name')->label('Departamento')->searchable()->sortable(), TextColumn::make('community.name')->label('Comunidad'), TextColumn::make('employees_count')->label('Empleados')->counts('employees'), TextColumn::make('appointments_count')->label('Citas')->counts('appointments'), TextColumn::make('tickets_count')->label('Tickets')->counts('tickets'), IconColumn::make('is_active')->label('Activo')->boolean()])
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
