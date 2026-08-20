<?php

namespace App\Filament\App\Resources\Employees\Pages;

use App\Filament\App\Resources\Employees\EmployeeResource;
use App\Filament\App\Widgets\EmployeesTableStatsWidget;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Archilex\AdvancedTables\AdvancedTables;

class ListEmployees extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = EmployeeResource::class;

    protected static ?string $title = 'Empleados';
    protected string $view = 'filament.app.resources.employees.pages.list-employees';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Vista pensada para edición y filtrado del empleado.';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            EmployeesTableStatsWidget::class,
        ];
    }

    public function getTabs(): array
    {
        $counts = $this->getTabCounts();

        return [
            'all' => Tab::make()
                ->label('Todos')
                ->badge($counts['all']),

            'swaps_pendientes' => Tab::make()
                ->label('Permisos swap pendientes')
                ->badge($counts['swaps_pending'])
                ->badgeColor('warning')
                ->icon('heroicon-o-clock')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereHas('shiftSwapRequests', function (Builder $query): Builder {
                    return $query->where('status', 'pending');
                })),

            'sin_swaps_pendientes' => Tab::make()
                ->label('Sin permisos swap pendientes')
                ->badge($counts['without_swaps_pending'])
                ->badgeColor('gray')
                ->icon('heroicon-o-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereDoesntHave('shiftSwapRequests', function (Builder $query): Builder {
                    return $query->where('status', 'pending');
                })),
        ];
    }

    protected function getTabCounts(): array
    {
        return Cache::remember(
            $this->getTabCountsCacheKey(),
            now()->addSeconds(20),
            function () {
                $baseQuery = $this->getBaseEmployeeQuery();

                $all = (int) $baseQuery->count();
                $swapsPending = (int) (clone $baseQuery)
                    ->whereHas('shiftSwapRequests', fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->count();
                $withoutSwapsPending = (int) (clone $baseQuery)
                    ->whereDoesntHave('shiftSwapRequests', fn (Builder $query): Builder => $query->where('status', 'pending'))
                    ->count();

                return [
                    'all' => $all,
                    'swaps_pending' => $swapsPending,
                    'without_swaps_pending' => $withoutSwapsPending,
                ];
            },
        );
    }

    protected function getTabCountsCacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('employees:list:tabs:%s', $panelId);
    }

    protected function getBaseEmployeeQuery(): Builder
    {
        return User::query()
            ->where('status', true)
            ->where(function (Builder $query): Builder {
                return $query->where('role', 'empleado')
                    ->orWhere('role', 'admin')
                    ->orWhere('role', 'super')
                    ->orWhere('is_employee', true);
            });
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Nuevo empleado')
                ->icon('heroicon-s-plus'),

            Action::make('Turnos')
                ->label('Turnos')
                ->icon('heroicon-s-calendar')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/shift-roster';
                }),
            Action::make('Asistencias')
                ->label('Asistencias')
                ->icon('heroicon-s-calendar')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/attendance-roster';
                }),
            Action::make('Permisos')
                ->label('Permisos')
                ->icon('heroicon-s-calendar')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/time-off-roster';
                }),
            Action::make('Metricas')
                ->label('Metricas')
                ->icon('heroicon-s-user-group')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/employee-metrics';
                }),
        ];
    }
}
