<?php

namespace App\Filament\App\Community\Actions;

use App\Actions\Community\RegenerateCommunityPlanWorkOrders;
use App\Models\CommunityPlan;
use App\Models\CommunityPlanItem;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class  GeneratePlanWorkOrdersAction
{
    public static function make(string $name = 'generate'): Action
    {
        return Action::make($name)
            ->label('Generar órdenes')
            ->icon('heroicon-o-arrow-path')
            ->color('success')
            ->visible(fn (CommunityPlan $record): bool => $record->status === 'active')
            ->modalHeading('Generar órdenes y asignar empleados')
            ->modalDescription('Selecciona el responsable de cada tarea entre los empleados capacitados para el tipo de servicio.')
            ->schema(fn (CommunityPlan $record): array => $record->items()
                ->with(['catalog.category', 'candidateEmployees' => fn ($query) => $query->where('active', true)->orderBy('name')])
                ->where('active', true)
                ->orderBy('sort')
                ->get()
                ->map(fn (CommunityPlanItem $item): Select => Select::make("assignments.{$item->id}")
                    ->label($item->title)
                    ->helperText(($item->catalog?->category?->name ?? 'Sin tipo').' · '.($item->catalog?->title ?? 'Servicio manual'))
                    ->options($item->candidateEmployees->pluck('name', 'id'))
                    ->placeholder($item->candidateEmployees->isEmpty() ? 'Sin candidatos configurados' : 'Selecciona un empleado')
                    ->disabled($item->candidateEmployees->isEmpty())
                    ->required($item->candidateEmployees->isNotEmpty())
                    ->default($item->candidateEmployees->first()?->id)
                    ->searchable())
                ->all())
            ->requiresConfirmation()
            ->action(function (CommunityPlan $record, array $data): void {
                app(RegenerateCommunityPlanWorkOrders::class)->handle($record, auth()->id(), $data['assignments'] ?? []);
                Notification::make()->title('Órdenes generadas y tareas asignadas')->success()->send();
            });
    }
}
