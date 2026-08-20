<?php

namespace App\Filament\App\Facturacion\Resources\TaskResource\Pages;

use App\Filament\App\Facturacion\Resources\TaskResource;
use App\Filament\Widgets\TaskCalendarWidget;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;

class CalendarTasks extends Page
{
    protected static string $resource = TaskResource::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?string $navigationLabel = 'Calendario';

    protected static ?string $title = 'Calendario de tareas';

    protected string $view = 'filament.app.resources.facturacion.resources.task-resource.pages.calendar-tasks';

    public function getWidgets(): array
    {
        return [
            TaskCalendarWidget::class,
        ];
    }

    public function getColumns(): int|string|array
    {
        return 1;
    }
}
