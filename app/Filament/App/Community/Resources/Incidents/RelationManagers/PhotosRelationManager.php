<?php

namespace App\Filament\App\Community\Resources\Incidents\RelationManagers;

use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class PhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([FileUpload::make('path')->label('Foto')->image()->directory('community/photos')->visibility('public')->required()]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([ImageColumn::make('path')->label('Foto')->disk('public'), TextColumn::make('filename')->label('Archivo'), TextColumn::make('created_at')->label('Fecha')->dateTime()])
        ->recordActions([
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])
        ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'community_id' => $this->getOwnerRecord()->community_id, 'filename' => basename($data['path']), 'uploaded_by' => auth()->id(), 'active' => true])]);
    }
}
