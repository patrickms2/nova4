<?php

namespace App\Filament\App\NovaHub\Resources\NovaAiProfiles\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaAiProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('name')->label('Perfil')->searchable()->sortable()->weight('bold'),
                TextColumn::make('provider')->label('Proveedor')->badge()->sortable(),
                TextColumn::make('model')->label('Modelo')->searchable()->sortable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('temperature')->label('Temp.')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label('Proveedor')
                    ->options([
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'google' => 'Google',
                        'local' => 'Local',
                        'other' => 'Otro',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }
}
