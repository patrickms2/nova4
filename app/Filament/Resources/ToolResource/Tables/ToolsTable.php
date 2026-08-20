<?php

declare(strict_types=1);

namespace App\Filament\Resources\ToolResource\Tables;

use Filament\Support\Icons\Heroicon;

use App\Models\Tool;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ToolsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('server.novaBusiness.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->badge()
                    ->color('primary'),

                TextColumn::make('server.name')
                    ->label('Servidor')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->copyable()
                    ->fontFamily('mono'),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->limit(35),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean(),

                TextColumn::make('logs_count')
                    ->counts('logs')
                    ->label('Llamadas')
                    ->alignCenter(),

                TextColumn::make('sort_order')
                    ->label('Orden')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('server_id')
            ->filters([
                SelectFilter::make('server')
                    ->label('Servidor')
                    ->relationship('server', 'name')->searchable(),

                TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->actions([
                Action::make('test')
                    ->label('Test')
                    ->icon(Heroicon::OutlinedPlay)
                    ->color('success')
                    ->url(fn (Tool $record) => route('filament.admin.pages.tool-tester', ['tool' => $record->id])),

                EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }
}
