<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;

use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProjectsStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $total = Project::count();
        $active = Project::where('status', 'active')->count();
        $completed = Project::where('phase', 'completed')->count();
        $inDevelopment = Project::where('phase', 'development')->count();

        return [
            Stat::make('Total Proyectos', $total)
                ->description('Todos los proyectos')
                ->descriptionIcon(Heroicon::OutlinedFolder)
                ->color('primary'),
            Stat::make('Activos', $active)
                ->description('Proyectos activos')
                ->descriptionIcon(Heroicon::OutlinedCheckCircle)
                ->color('success'),
            Stat::make('En Desarrollo', $inDevelopment)
                ->description('En fase de desarrollo')
                ->descriptionIcon(Heroicon::OutlinedCodeBracket)
                ->color('warning'),
            Stat::make('Completados', $completed)
                ->description('Proyectos finalizados')
                ->descriptionIcon(Heroicon::OutlinedFlag)
                ->color('info'),
        ];
    }
}
