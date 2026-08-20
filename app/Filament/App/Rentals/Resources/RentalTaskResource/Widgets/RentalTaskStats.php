<?php

namespace App\Filament\App\Rentals\Resources\RentalTaskResource\Widgets;

use App\Enums\TaskStatus;
use App\Models\Task;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RentalTaskStats extends BaseWidget
{
    protected function getStats(): array
    {
        $total = Task::query()->count();

        return [
            Stat::make('Total tareas', (string) $total)
                ->description('registradas')
                ->descriptionIcon('heroicon-o-clipboard-document-list')
                ->color('gray'),
            Stat::make('Pendientes', (string) Task::where('status', TaskStatus::Todo->value)->orWhere('status', 'pending')->count())
                ->description('por hacer')
                ->descriptionIcon('heroicon-o-clock')
                ->color('info'),
            Stat::make('En progreso', (string) Task::where('status', TaskStatus::InProgress->value)->orWhere('status', 'in_progress')->count())
                ->description('activas')
                ->descriptionIcon('heroicon-o-arrow-path')
                ->color('warning'),
            Stat::make('Completadas', (string) Task::where('status', TaskStatus::Completed->value)->orWhere('status', 'completed')->count())
                ->description('finalizadas')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),
        ];
    }
}
