<?php

namespace App\Filament\Resources\NovaIntentToServerMapping\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Table;

class NovaIntentToServerMappingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('intent_key')
                    ->label('Intent')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('server.name')
                    ->label('Server')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tool.name')
                    ->label('Tool')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('priority')
                    ->label('Priority')
                    ->sortable(),
                TextColumn::make('business.name')
                    ->label('Business')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('Global'),
                BooleanColumn::make('is_active')
                    ->label('Active'),
            ])
            ->defaultSort('priority', 'desc')
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
