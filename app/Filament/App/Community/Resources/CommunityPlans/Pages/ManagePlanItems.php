<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use App\Filament\App\Community\Support\PlanItemForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ManagePlanItems extends ManageRelatedRecords
{
    protected static string $resource = CommunityPlanResource::class;

    protected static string $relationship = 'items';

    protected static ?string $title = 'Plan items';

    protected static ?string $navigationLabel = 'Plan items';

    public function form(Schema $schema): Schema
    {
        return $schema->components(PlanItemForm::components());
    }

    public function table(Table $table): Table
    {
        return $table->reorderable('sort')->columns([TextColumn::make('title')->label('Tarea')->searchable(), TextColumn::make('catalog.category.name')->label('Tipo')->badge(), TextColumn::make('catalog.title')->label('Servicio'), TextColumn::make('candidateEmployees.name')->label('Candidatos')->badge(), TextColumn::make('days.day_of_week')->label('Días')->formatStateUsing(fn ($state): string => match ((int) $state) {
            1 => 'Lun',2 => 'Mar',3 => 'Mié',4 => 'Jue',5 => 'Vie',6 => 'Sáb',7 => 'Dom',default => (string) $state
        })->badge(), IconColumn::make('active')->label('Activa')->boolean()])
            ->recordActions([
                EditAction::make('Editar'),
                DeleteAction::make('Eliminar'),

            ])
            ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data,'community_plan_id' => $this->getOwnerRecord()->id, 'created_by' => auth()->id(), 'updated_by' => auth()->id()])]);
    }
}
