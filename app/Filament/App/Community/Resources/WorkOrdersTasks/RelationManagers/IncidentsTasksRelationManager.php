<?php

namespace App\Filament\App\Community\Resources\WorkOrdersTasks\RelationManagers;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
class IncidentsTasksRelationManager extends RelationManager
{
    protected static string $relationship = 'incidents';

    protected static ?string $title = 'Incidencias';

    public function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required(), Textarea::make('description')->required(), Select::make('priority')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), Select::make('status')->options(['open' => 'Abierta', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'closed' => 'Cerrada'])->default('open')]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')->label('Incidencia'), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('created_at')->label('Fecha')->dateTime()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'community_id' => $this->getOwnerRecord()->community_id, 'created_by' => auth()->id(), 'updated_by' => auth()->id()])]);
    }
}
