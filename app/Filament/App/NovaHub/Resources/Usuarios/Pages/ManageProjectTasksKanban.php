<?php

namespace App\Filament\App\NovaHub\Resources\Usuarios\Pages;

use App\Enums\Priority;
use App\Enums\TaskStatus;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Task;
use Asmit\AdvancedKanban\Actions\ActionGroup;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Concerns\HasKanbanRelatedRecords;
use Asmit\AdvancedKanban\Contracts\HasKanban;
use Asmit\AdvancedKanban\Kanban;
use Asmit\AdvancedKanban\RecordAction\DeleteAction;
use Asmit\AdvancedKanban\RecordAction\EditAction;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ManageProjectTasksKanban extends ManageRelatedRecords implements HasKanban
{
    use HasKanbanRelatedRecords;

    protected static string $resource = ProjectResource::class;

    protected static string $relationship = 'tasks';

    public function getColumnHeaderComponent(): string
    {
        return 'kanban.task-resource.column-header';
    }

    public function getCardComponent(): string
    {
        return 'kanban.task-resource.card';
    }

    public function getShouldPersistSearchInSession(): bool
    {
        return true;
    }

    public function getShouldPersistFiltersInSession(): bool
    {
        return true;
    }

    public function getTabs(): array
    {
        return [
            Tab::make('all')
                ->label('All')
                ->icon(Heroicon::OutlinedSquare3Stack3d),
            Tab::make('pending')
                ->label('Pending')
                ->icon(Heroicon::OutlinedClock)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'pending')),

            Tab::make('in_progress')
                ->label('In Progress')
                ->icon(Heroicon::OutlinedArrowPath)
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', 'in_progress')),
        ];
    }

    public function kanban(Kanban $kanban): Kanban
    {
        return $kanban
            ->model(Task::class)
            ->statusField('status')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['assignedTo'])->orderBy('created_at', 'desc'))
            ->searchableFields(['title', 'description'])
            ->enableLoadingIndicator()
            ->enableFilterIndicator()
            ->columns([
                KanbanColumn::make('pending')
                    ->lockCardUsing(fn (Task $record) => $record->unassigned())
                    ->lockedLabel('Unassigned Tasks')
                    ->icon(Heroicon::OutlinedClock)
                    ->allowedTransitions(['in_progress', 'archived']),

                KanbanColumn::make('in_progress')
                    ->lockCardUsing(fn (Task $record) => $record->unassigned())
                    ->lockedLabel('Unassigned Tasks')
                    ->icon(Heroicon::OutlinedArrowPath)
                    ->allowedTransitions(['review', 'pending', 'archived']),

                KanbanColumn::make('review')
                    ->lockCardUsing(fn (Task $record) => $record->unassigned())
                    ->lockedLabel('Unassigned Tasks')
                    ->icon(Heroicon::OutlinedEye)
                    ->allowedTransitions(['completed', 'in_progress', 'archived']),

                KanbanColumn::make('completed')
                    ->lockCardUsing(fn (Task $record) => $record->unassigned())
                    ->lockedLabel('Unassigned Tasks')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->allowedTransitions(['archived', 'pending']),

                KanbanColumn::make('archived')
                    ->lockCardUsing(fn (Task $record) => $record->unassigned())
                    ->lockedLabel('Unassigned Tasks')
                    ->icon(Heroicon::OutlinedArchiveBox)
                    ->allowedTransitions(['pending']),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make('edit')
                        ->model(Task::class)
                        ->schema(fn () => $this->taskForm(null)),
                    DeleteAction::make('delete')
                        ->icon(Heroicon::OutlinedTrash)
                        ->requiresConfirmation()
                        ->color('danger'),
                ]),
            ])
            ->columnHeaderActions([
                CreateAction::make()
                    ->model(Task::class)
                    ->schema(function (array $arguments): array {
                        return $this->taskForm($arguments['status']);
                    })
                    ->icon(Heroicon::OutlinedPlus)
                    ->hiddenLabel()
                    ->link(),
            ])
            ->filterFormSchema([
                Select::make('status')
                    ->options(TaskStatus::class)
                    ->default([TaskStatus::PENDING, TaskStatus::IN_PROGRESS, TaskStatus::COMPLETED])
                    ->multiple()
                    ->nullable(),

                Select::make('priority')
                    ->options(Priority::class)
                    ->default([Priority::LOW, Priority::HIGH])
                    ->multiple()
                    ->nullable(),
            ])
            ->applyFiltersUsing(function (Builder $query, array $filters): Builder {
                if (! empty($filters['status'])) {
                    $query->whereIn('status', $filters['status']);
                }
                if (! empty($filters['priority'])) {
                    $query->whereIn('priority', $filters['priority']);
                }

                return $query;
            });
    }

    private function taskForm($status): array
    {
        return [
            Select::make('status')
                ->options(TaskStatus::class)
                ->default($status),

            Select::make('project_id')
                ->required()
                ->default(fn () => $this->getOwnerRecord()?->id)
                ->searchable()
                ->relationship('project', 'name'),

            TextInput::make('title')
                ->required()
                ->maxLength(255),

            Textarea::make('description')
                ->maxLength(65535)
                ->columnSpanFull(),

            Select::make('assigned_to')
                ->searchable()
                ->relationship('assignedTo', 'name')->searchable()
                ->nullable(),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('Project Tasks List')
                ->url(fn () => ManageProjectTasks::getUrl(['record' => $this->getOwnerRecord()]))
                ->icon(Heroicon::ListBullet),
        ];
    }

    public function viewAction(): \Filament\Actions\Action
    {
        return \Filament\Actions\Action::make('view')
            ->label('Task Details')
            ->slideOver()
            ->record(fn (array $arguments) => Task::query()->with(['project', 'assignedTo'])->find($arguments['recordId']))
            ->modalSubmitAction(false)
            ->schema(function ($record) {
                return [
                    TextEntry::make('Title')
                        ->default($record->title),

                    TextEntry::make('Description')
                        ->default($record->description),

                    TextEntry::make('status')
                        ->badge()
                        ->icon($record->status->getIcon())
                        ->color($record->status->getColor())
                        ->default($record->status->getLabel()),

                    TextEntry::make('priority')
                        ->badge()
                        ->icon($record->priority->getIcon())
                        ->color($record->priority->getColor())
                        ->default($record->priority->getLabel()),

                    TextEntry::make('Project')
                        ->default($record->project->name),

                    TextEntry::make('Assigned To')
                        ->badge()
                        ->default($record->assignedTo?->name ?? 'Unassigned'),

                    TextEntry::make('due_date')
                        ->default($record->due_date?->toFormattedDateString() ?? 'No due date'),
                ];
            });
    }
}
