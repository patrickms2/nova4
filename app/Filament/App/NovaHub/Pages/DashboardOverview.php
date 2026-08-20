<?php

namespace App\Filament\App\NovaHub\Pages;

use Filament\Support\Icons\Heroicon;

use App\Filament\Widgets\InvoicesStatsWidget;
use App\Filament\Widgets\NotesStatsWidget;
use App\Filament\Widgets\ProjectsStatsWidget;
use App\Filament\Widgets\TasksStatsWidget;
use Filament\Pages\Page;

class DashboardOverview extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedSquaresPlus;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static string|\UnitEnum|null $navigationGroup = 'General';

    protected static ?int $navigationSort = 1;

    protected  string $view = 'filament.pages.dashboard-overview';

    protected function getHeaderWidgets(): array
    {
        return [
            ProjectsStatsWidget::class,
            TasksStatsWidget::class,
            NotesStatsWidget::class,
            InvoicesStatsWidget::class,
        ];
    }
}
