<?php

namespace App\Filament\App\Facturacion\Resources\TaskResource\Pages;

use App\Filament\App\Facturacion\Resources\TaskResource;
use App\Filament\Widgets\TasksStatsWidget;
use App\Models\Task;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListTasks extends ListRecords
{
    protected static string $resource = TaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('calendar')
                ->label('Calendario')
                ->icon(Heroicon::OutlinedCalendarDays)
                ->url(static::getResource()::getUrl('calendar')),
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            TasksStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        return [
            'all' => $this->makeStatusTab('all', 'Todas', Heroicon::OutlinedListBullet, 'primary'),
            'pending' => $this->makeStatusTab('pending', 'Pendiente', Heroicon::OutlinedClock, 'warning'),
            'in_progress' => $this->makeStatusTab('in_progress', 'En progreso', Heroicon::OutlinedArrowPath, 'info'),
            'completed' => $this->makeStatusTab('completed', 'Completada', Heroicon::OutlinedCheck, 'success'),
            'cancelled' => $this->makeStatusTab('cancelled', 'Cancelada', Heroicon::OutlinedXCircle, 'danger'),
        ];
    }

    private function makeStatusTab(string $status, string $label, Heroicon $icon, string $color): Tab
    {
        $badgeQuery = Task::query();
        if ($status !== 'all') {
            $badgeQuery->where('status', $status);
        }

        return Tab::make()
            ->label($label)
            ->icon($icon)
            ->badge(fn (): int => $badgeQuery->count())
            ->badgeColor($color)
            ->modifyQueryUsing(function (Builder $query) use ($status): Builder {
                if ($status === 'all') {
                    return $query;
                }

                return $query->where('status', $status);
            });
    }
}
