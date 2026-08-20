<?php

namespace App\Filament\App\Rentals\Resources\RentalTaskResource\Pages;

use App\Filament\App\Rentals\Resources\RentalTaskResource;
use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class CalendarRentalTasks extends Page
{
    protected static string $resource = RentalTaskResource::class;

    protected static ?string $title = 'Calendario de tareas';

    protected string $view = 'filament::pages.page';

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

    protected function getHeaderActions(): array
    {
        return [
            Action::make('listado')
                ->label('Listado')
                ->icon('heroicon-o-list-bullet')
                ->url(RentalTaskResource::getUrl('index')),

            Action::make('kanban')
                ->label('Kanban')
                ->icon('heroicon-o-view-columns')
                ->url(RentalTaskResource::getUrl('kanban')),
        ];
    }

    /**
     * @return array<class-string<Widget>>
     */
    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Resources\RentalTaskResource\Widgets\RentalTasksCalendar::class,
        ];
    }
}
