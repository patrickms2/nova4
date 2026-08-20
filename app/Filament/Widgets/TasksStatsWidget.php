<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;

use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TasksStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $total = Task::count();
        $pending = Task::where('status', 'pending')->count();
        $inProgress = Task::where('status', 'in_progress')->count();
        $completed = Task::where('status', 'completed')->count();
        $highPriority = Task::where('priority', 'high')->count();

        return [
            Stat::make('Total Tareas', $total)
                ->description('Todas las tareas')
                ->descriptionIcon(Heroicon::OutlinedListBullet)
                ->color('primary'),
            Stat::make('Pendientes', $pending)
                ->description('Tareas pendientes')
                ->descriptionIcon(Heroicon::OutlinedClock)
                ->color('warning'),
            Stat::make('En Progreso', $inProgress)
                ->description('Tareas en curso')
                ->descriptionIcon(Heroicon::OutlinedArrowPath)
                ->color('info'),
            Stat::make('Completadas', $completed)
                ->description('Tareas finalizadas')
                ->descriptionIcon(Heroicon::OutlinedCheck)
                ->color('success'),
            Stat::make('Alta Prioridad', $highPriority)
                ->description('Tareas urgentes')
                ->descriptionIcon(Heroicon::OutlinedExclamationTriangle)
                ->color('danger'),
        ];
    }
}
