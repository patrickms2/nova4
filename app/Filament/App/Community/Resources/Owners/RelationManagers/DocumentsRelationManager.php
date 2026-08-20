<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'communityDocuments';

    protected static ?string $title = 'Documentos';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('community_id')->relationship('community', 'name')->required(), Select::make('property_id')->relationship('property', 'name'), Select::make('type')->options(['ownership' => 'Propiedad', 'fee' => 'Cuota', 'insurance' => 'Seguro', 'minutes' => 'Acta', 'other' => 'Otro'])->required(), TextInput::make('title')->required(), FileUpload::make('path')->disk('local')->directory('community/owners')->visibility('private')->required(), Select::make('status')->options(['active' => 'Activo', 'expired' => 'Caducado', 'archived' => 'Archivado'])->default('active'), DatePicker::make('document_date')->label('Fecha'), DatePicker::make('expires_at')->label('Caduca')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('title')->label('Documento'), TextColumn::make('type')->label('Tipo')->badge(), TextColumn::make('property.name')->label('Propiedad'), TextColumn::make('expires_at')->label('Caduca')->date(), TextColumn::make('status')->label('Estado')->badge()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $d): array => [...$d, 'uploaded_by' => auth()->id()])]);
    }
}
