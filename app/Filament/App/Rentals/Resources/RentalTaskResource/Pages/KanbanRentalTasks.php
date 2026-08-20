<?php

namespace App\Filament\App\Rentals\Resources\RentalTaskResource\Pages;

use App\Filament\App\Rentals\Resources\RentalTaskResource;
use App\Enums\TaskStatus;
use App\Models\Task;
use Asmit\AdvancedKanban\Concerns\InteractsWithKanban;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class KanbanRentalTasks extends Page implements HasKanban
{
    use InteractsWithKanban;

    protected static bool $shouldRegisterNavigation = false;

    protected static string $resource = RentalTaskResource::class;

    protected string $view = 'advanced-kanban::index';

    protected static string $model = \App\Models\Task::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'status';

    protected static ?string $title = 'Kanban de tareas';

    public function getColumnHeaderComponent(): string
    {
        return 'advanced-kanban::column-header';
    }

    public function getCardComponent(): string
    {
        return 'advanced-kanban::card';
    }

    private function makeStatusTab(TaskStatus $status): Tab
    {
        return Tab::make()
            ->label($status->getLabel() ?? ucfirst($status->value))
            ->badge(fn (): int => Task::where('status', $status->value)->count())
            ->badgeColor($status->getColor())
            ->icon($status->getIcon())
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', $status->value));
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make()
                ->label('Todas')
                ->badge(fn (): int => Task::count()),
            'todo' => $this->makeStatusTab(TaskStatus::Todo),
            'in_progress' => $this->makeStatusTab(TaskStatus::InProgress),
            'in_review' => $this->makeStatusTab(TaskStatus::InReview),
            'completed' => $this->makeStatusTab(TaskStatus::Completed),
        ];
    }

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $allowed = array_map(fn (TaskStatus $status): string => $status->value, TaskStatus::cases());

        if (! in_array($newStatus, $allowed, true)) {
            return;
        }

        if ($record instanceof Task) {
            $record->update(['status' => $newStatus]);
        }
    }

    public function kanban(Kanban $kanban): Kanban
    {
        return $kanban
            ->model(static::$model)
            ->statusField(static::$recordStatusAttribute)
            ->titleField(static::$recordTitleAttribute)
            ->descriptionField('description')
            ->searchableFields(['title', 'description'])
            ->enableLoadingIndicator()
            ->recordsPerColumn(15)
            ->modifyQueryUsing(function (Builder $query): Builder {
                return $query->with(['assignedTo'])->orderByDesc('updated_at');
            })
            ->columns([
                KanbanColumn::make('todo')
                    ->label('Por hacer')
                    ->iconcolor('info')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'todo')),

                KanbanColumn::make('in_progress')
                    ->label('En progreso')
                    ->iconcolor('warning')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'in_progress')),

                KanbanColumn::make('in_review')
                    ->label('En revisión')
                    ->iconcolor('primary')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'in_review')),

                KanbanColumn::make('completed')
                    ->label('Completada')
                    ->iconcolor('success')
                    ->modifyRecordQueryUsing(fn (Builder $query): Builder => $query->where('status', 'completed')),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('calendar')
                ->label('Calendario')
                ->icon('heroicon-o-calendar-days')
                ->color('warning')
                ->url(RentalTaskResource::getUrl('calendar')),

            Action::make('table')
                ->label('Listado')
                ->icon('heroicon-o-table-cells')
                ->url(RentalTaskResource::getUrl('index')),
        ];
    }
}
