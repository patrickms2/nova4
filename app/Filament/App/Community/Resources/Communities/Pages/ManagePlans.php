<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use App\Filament\App\Community\Support\PlanItemForm;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\SelectFilter;

class ManagePlans extends ManageRelatedRecords
{
    protected static string $resource = CommunityResource::class;

    protected static string $relationship = 'plans';

    protected static ?string $title = 'Planes';

    protected static ?string $navigationLabel = 'Planes';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationParentGroup = 'Nova Community';
    public function form(Schema $schema): Schema
    {
        return $schema->components(PlanItemForm::components());
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('valid_from', 'desc')->columns([TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), TextColumn::make('name')->label('Nombre'), TextColumn::make('valid_from')->label('Desde')->date()->sortable(), TextColumn::make('valid_until')->label('Hasta')->date()->placeholder('Sin fin'), TextColumn::make('items_count')->label('Tareas')->counts('items'), TextColumn::make('status')->label('Estado')->badge()])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo', 'replaced' => 'Sustituido'])])
        ->recordActions([
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])->headerActions([
            CreateAction::make(),
        ]);
    }
}
