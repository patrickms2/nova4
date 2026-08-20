<?php

namespace App\Filament\Widgets;

use Filament\Support\Icons\Heroicon;

use App\Models\Note;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class NotesStatsWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;
    protected function getStats(): array
    {
        $total = Note::count();
        $pinned = Note::where('is_pinned', true)->count();
        $thisWeek = Note::where('created_at', '>=', now()->startOfWeek())->count();
        $withTags = Note::whereNotNull('tags')->where('tags', '!=', '[]')->count();

        return [
            Stat::make('Total Notas', $total)
                ->description('Todas las notas')
                ->descriptionIcon(Heroicon::OutlinedDocumentText)
                ->color('primary'),
            Stat::make('Fijadas', $pinned)
                ->description('Notas destacadas')
                ->descriptionIcon(Heroicon::OutlinedMapPin)
                ->color('warning'),
            Stat::make('Esta Semana', $thisWeek)
                ->description('Notas creadas esta semana')
                ->descriptionIcon(Heroicon::OutlinedCalendar)
                ->color('info'),
            Stat::make('Con Etiquetas', $withTags)
                ->description('Notas organizadas')
                ->descriptionIcon(Heroicon::OutlinedTag)
                ->color('success'),
        ];
    }
}
