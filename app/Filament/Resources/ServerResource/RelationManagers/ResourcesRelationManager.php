<?php

namespace App\Filament\Resources\ServerResource\RelationManagers;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema as Form;
use Filament\Tables;
use Filament\Tables\Table;
class ResourcesRelationManager extends RelationManager
{
    protected static string $relationship = 'resources';
    protected static ?string $title = 'Resources';
    public function form(Form $schema): Form
    {
        return $schema->schema([
            Forms\Components\TextInput::make('name')
                ->required()
                ->placeholder('api-docs'),
            Forms\Components\TextInput::make('title')
                ->placeholder('API Documentation'),
            Forms\Components\TextInput::make('uri')
                ->placeholder('myapp://docs/api'),
            Forms\Components\Select::make('mime_type')
                ->options([
                    'text/plain' => 'Plain Text',
                    'text/markdown' => 'Markdown',
                    'text/html' => 'HTML',
                    'application/json' => 'JSON',
                ])
                ->default('text/plain')
                ->required(),
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
                Tables\Columns\TextColumn::make('uri')
                    ->limit(30),
                Tables\Columns\TextColumn::make('mime_type')
                    ->badge(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
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
