<?php

namespace App\Filament\App\Community\Resources\WorkOrders\Pages;

use App\Filament\App\Community\Resources\WorkOrders\WorkOrderResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Illuminate\Database\Eloquent\Builder;
use App\Models\WorkOrder;
use App\Actions\Community\TransitionWorkOrder;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
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

class ManageIncidents extends ManageRelatedRecords
{
    protected static string $relationship = 'incidents';
    protected static string $resource = WorkOrderResource::class;

    protected static ?string $title = 'Incidencias';
    protected static ?string $navigationLabel = 'Incidencias en tarea';

    public function form(Schema $schema): Schema
    {
        return $schema->components([TextInput::make('title')->required(), Textarea::make('description')->required(), Select::make('priority')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), Select::make('status')->options(['open' => 'Abierta', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'closed' => 'Cerrada'])->default('open')]);
    }

    public function table(Table $table): Table
    {
        return $table->columns([TextColumn::make('title')->label('Incidencia'), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('created_at')->label('Fecha')->dateTime()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'community_id' => $this->getOwnerRecord()->community_id, 'created_by' => auth()->id(), 'updated_by' => auth()->id()])]);
    }
}
