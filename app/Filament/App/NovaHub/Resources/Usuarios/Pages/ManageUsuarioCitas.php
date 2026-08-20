<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use App\Filament\App\NovaHub\Resources\Usuarios\UsuariosResource;

/* use App\Filament\Widgets\LocationStatsOverview; */

use App\Models\Taxi\Municipio;
use App\Models\Taxi\Usuario;
use Filament\Resources\Pages\ListRecords;
use App\Filament\App\NovaHub\Resources\Citas\CitaResource;

use App\Filament\Forms\CitaForm;
use App\Filament\Resources\Tasks\Tables\TasksTable;
use App\Models\Task;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ManageUsuarioCitas extends ManageRelatedRecords
{
    protected static string $resource = CitaResource::class;

    protected static string $relationship = 'citas';

    protected static bool $shouldRegisterNavigation = false;

    public function table(Table $table): Table
    {
        return TasksTable::configure($table)
            ->modifyQueryUsing(fn($query) => $query->where('project_id', $this->getOwnerRecord()?->id));
    }

    public function form(Schema $schema): Schema
    {
        return TaskForm::configure($schema);
    }

    protected function getHeaderActions(): array
    {
        return [

            \Filament\Actions\Action::make('Kanban View')
                ->url(fn() => ManageProjectTasksKanban::getUrl(['record' => $this->getOwnerRecord()]))
                ->icon(Heroicon::OutlinedSquares2x2),
            CreateAction::make('create')
                ->model(Task::class),
        ];
    }
}
