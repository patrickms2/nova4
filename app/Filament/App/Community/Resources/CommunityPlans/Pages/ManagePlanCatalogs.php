<?php

namespace App\Filament\App\Community\Resources\CommunityPlans\Pages;

use App\Filament\App\Community\Resources\CommunityPlans\CommunityPlanResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;

class ManagePlanCatalogs extends ManageRelatedRecords
{
    protected static string $resource = CommunityPlanResource::class;

    protected static string $relationship = 'catalogs';

    protected static ?string $title = 'Tipos Servicios';

    protected static ?string $navigationLabel = 'Tipos Servicios';

    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('work_catalog_id')->label('Catálogo')->relationship('catalog', 'title')->searchable()->preload(), TextInput::make('title')->label('Tarea')->required(), Textarea::make('instructions')->label('Instrucciones'), Textarea::make('requirements')->label('Requisitos'), TextInput::make('sort')->label('Orden')->numeric()->default(0), Toggle::make('active')->label('Activa')->default(true), Repeater::make('days')->label('Días')->relationship()->schema([Select::make('day_of_week')->label('Día')->options([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'])->required()])->columnSpanFull()]);
    }

    public function table(Table $table): Table
    {
        return $table->defaultSort('title')->columns([
            TextColumn::make('title')->label('Servicio')->searchable()->sortable(),  IconColumn::make('active')->label('Activo')->boolean(),
        ])->filters([ TernaryFilter::make('active')->label('Activo')])
             ->recordActions([
                EditAction::make('Editar'),
                DeleteAction::make('Eliminar'),

            ])
            ->headerActions([CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'created_by' => auth()->id(), 'updated_by' => auth()->id()])]);
    }
}
