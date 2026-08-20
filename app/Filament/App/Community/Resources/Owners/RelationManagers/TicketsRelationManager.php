<?php

namespace App\Filament\App\Community\Resources\Owners\RelationManagers;

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
class TicketsRelationManager extends RelationManager
{
    protected static string $relationship = 'communityTickets';

    protected static ?string $title = 'Tickets';

    public function form(Schema $s): Schema
    {
        return $s->components([Select::make('community_id')->relationship('community', 'name')->required(), Select::make('property_id')->relationship('property', 'name'), Select::make('community_department_id')->relationship('department', 'name'), TextInput::make('title')->required(), Textarea::make('description'), Select::make('priority')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal'), Select::make('status')->options(['open' => 'Abierto', 'in_progress' => 'En curso', 'resolved' => 'Resuelto', 'closed' => 'Cerrado'])->default('open')]);
    }

    public function table(Table $t): Table
    {
        return $t->columns([TextColumn::make('title')->label('Ticket'), TextColumn::make('property.name')->label('Propiedad'), TextColumn::make('priority')->label('Prioridad')->badge(), TextColumn::make('status')->label('Estado')->badge(), TextColumn::make('created_at')->label('Fecha')->dateTime()])->headerActions([CreateAction::make()->mutateDataUsing(fn (array $d): array => [...$d, 'created_by' => auth()->id()])]);
    }
}
