<?php

namespace App\Filament\Widgets;

use App\Filament\App\Facturacion\Resources\TaskResource;
use App\Models\Task;
use Carbon\Carbon;
use Saade\FilamentFullCalendar\Data\EventData;
use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;

class TaskCalendarWidget extends FullCalendarWidget
{
    public function fetchEvents(array $info): array
    {
        $start = $info['start'] ?? now()->subDays(30)->toDateString();
        $end = $info['end'] ?? now()->addDays(60)->toDateString();

        return Task::query()
            ->whereBetween('due_date', [$start, $end])
            ->with(['project', 'assignedTo'])
            ->get()
            ->map(fn (Task $task) => EventData::make()
                ->id('task-'.$task->id)
                ->title($this->buildEventTitle($task))
                ->start(Carbon::parse($task->due_date)->toDateString())
                ->end(Carbon::parse($task->due_date)->addDay()->toDateString())
                ->allDay(true)
                ->backgroundColor($this->statusColor($task->status))
                ->borderColor($this->statusColor($task->status))
                ->extendedProps([
                    'status' => $task->status,
                    'project' => $task->project?->name,
                ])
                ->toArray())
            ->all();
    }

    protected function buildEventTitle(Task $task): string
    {
        $parts = [trim($task->title)];

        if ($task->project?->name) {
            $parts[] = $task->project->name;
        }

        return implode(' — ', $parts);
    }

    protected function statusColor(string $status): string
    {
        return match ($status) {
            'pending' => '#f59e0b',
            'in_progress' => '#3b82f6',
            'completed', 'done' => '#10b981',
            'cancelled' => '#ef4444',
            default => '#6b7280',
        };
    }

    public function onEventClick(array $event): void
    {
        $id = $event['event']['id'] ?? $event['id'] ?? null;

        if (! is_string($id) || ! str_starts_with($id, 'task-')) {
            return;
        }

        $taskId = substr($id, strlen('task-'));

        $this->redirect(TaskResource::getUrl('edit', ['record' => $taskId]));
    }

    public function getFormSchema(): array
    {
        return [];
    }
}
