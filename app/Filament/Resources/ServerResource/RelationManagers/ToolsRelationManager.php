<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class ToolsRelationManager extends RelationManager
{
    protected static string $relationship = 'tools';
    protected static ?string $title = 'Tools';
    public function form(Form $schema): Form
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->placeholder('get-weather'),
            Forms\Components\TextInput::make('title')
                ->placeholder('Get Weather'),
            Forms\Components\Textarea::make('description')
                ->rows(2),
            Forms\Components\Toggle::make('is_active')
                ->default(true),
        ]);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->sortable()
                    ->numeric(false),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->headerActions([
                Actions\CreateAction::make(),
            ])
            ->actions([
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
            ])
            ->toolbarActions([
                Actions\BulkActionGroup::make([
                    Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
