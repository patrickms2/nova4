<?php

namespace App\Filament\App\Community\Resources\Incidents\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CommentsRelationManager extends RelationManager
{
    protected static string $relationship = 'comments';

    protected static ?string $title = 'Comentarios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([Textarea::make('description')->label('Comentario')->required()]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('description')->label('Comentario')->wrap(), TextColumn::make('user.name')->label('Usuario'), TextColumn::make('created_at')->label('Fecha')->since()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'user_id' => auth()->id(), 'active' => true])]);
    }
}
