<?php

namespace App\Filament\App\NovaHub\Resources\NovaWhatsappChannels\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class NovaWhatsappChannelsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('business.name')->label('Cliente')->searchable()->sortable(),
                TextColumn::make('name')->label('Canal')->searchable()->sortable()->weight('bold'),
                TextColumn::make('provider')->label('Proveedor')->badge()->sortable(),
                TextColumn::make('phone_number')->label('Número')->searchable(),
                TextColumn::make('status')->label('Estado')->badge()->sortable(),
                TextColumn::make('updated_at')->label('Actualizado')->dateTime('d/m/Y H:i')->sortable()->toggleable(),
            ])
            ->filters([
                SelectFilter::make('provider')
                    ->label('Proveedor')
                    ->options([
                        'meta' => 'Meta WhatsApp Cloud',
                        'twilio' => 'Twilio',
                        '360dialog' => '360dialog',
                        'other' => 'Otro',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'draft' => 'Borrador',
                        'active' => 'Activo',
                        'paused' => 'Pausado',
                        'error' => 'Error',
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
