<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Usuarios\Pages;

use App\Filament\App\Resources\Usuarios\UsuariosResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use App\Models\Taxista;

/* use App\Filament\Widgets\LocationStatsOverview; */
/*use App\Filament\Widgets\LocationMapTableWidget;
use App\Filament\Widgets\LocationMapWidget;*/
/* use App\Filament\Pages\Actions\ImpersonatePageAction; */

/* use Archilex\AdvancedTables\Filters\UserSelectFilter; */

final class ListUsuarios extends ListRecords
{
    use AdvancedTables;
    protected $listeners = [
        'echo:taxistas,.taxista.presence.updated' => '$refresh',
        'echo:taxistas,.taxista.location.updated' => '$refresh',
    ];
    protected static ?string    $title = 'Usuarios';
    // protected string $view = 'filament.resources.usuarios-resource.pages.list-usuarios';

    protected static string $resource = UsuariosResource::class;

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';   // ← Acción por defecto al hacer clic en una fila
    }

    protected function getDefaultTableAction(): ?string
    {
        return 'edit';
    }

    protected function getHeaderWidgets(): array
    {
        return UsuariosResource::getWidgets();
    }


protected function getTabCounts(): array
    {
        $today = today()->toDateString();

        return Cache::remember(
            $this->getTabCountsCacheKey(),
            now()->addSeconds(20),
            static function () use ($today): array {
                /** @var object{all_count:int|string|null,active_count:int|string|null,inactive_count:int|string|null,new_today_count:int|string|null}|null $totals */
                $totals = Taxista::query()
                    ->selectRaw('COUNT(*) as all_count')
                    ->selectRaw('SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as active_count')
                    ->selectRaw('SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as inactive_count')
                    ->selectRaw('SUM(CASE WHEN DATE(created_at) = ? THEN 1 ELSE 0 END) as new_today_count', [$today])
                    ->first();

                $all = (int) ($totals->all_count ?? 0);
                $withAppointments = (int) TaxistaAppointment::query()
                    ->whereNotNull('taxista_user_id')
                    ->distinct()
                    ->count('taxista_user_id');
                $withTickets = (int) TaxistaTicket::query()
                    ->whereNotNull('user_id')
                    ->distinct()
                    ->count('user_id');

                return [
                    'all' => $all,
                    'active' => (int) ($totals->active_count ?? 0),
                    'inactive' => (int) ($totals->inactive_count ?? 0),
                    'with_taxis' => (int) TaxistaTaxi::query()
                        ->whereNotNull('taxista_user_id')
                        ->distinct()
                        ->count('taxista_user_id'),
                    'with_licencia' => (int) Taxista::query()
                        ->whereNotNull('licencia')
                        ->where('licencia', '!=', '')
                        ->distinct()
                        ->count('id'),
                    'without_licencia' => (int) Taxista::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->whereNull('licencia')
                                ->orWhere('licencia', '');
                        })
                        ->distinct()
                        ->count('id'),
                    'without_nif' => (int) Taxista::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->whereNull('nif')
                                ->orWhere('nif', '');
                        })
                        ->distinct()
                        ->count('id'),
                    'without_municipio' => (int) Taxista::query()
                        ->whereNull('municipio_id')
                        ->distinct()
                        ->count('id'),
                    'with_appointments' => $withAppointments,
                    'without_appointments' => max(0, $all - $withAppointments),
                    'with_tickets' => $withTickets,
                    'open_tickets' => (int) TaxistaTicket::query()
                        ->whereIn('status', ['abierto', 'en_proceso'])
                        ->whereNotNull('user_id')
                        ->distinct()
                        ->count('user_id'),
                    'without_tickets' => max(0, $all - $withTickets),
                    'new_today' => (int) ($totals->new_today_count ?? 0),
                ];
            },
        );
    }

    protected function getTabCountsCacheKey(): string
    {
        $panelId = \Filament\Facades\Filament::getCurrentPanel()?->getId() ?? 'panel';

        return sprintf('taxistas:list:tabs:%s', $panelId);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('empleados')
                ->label('Servicios Hoteles')
                ->icon('heroicon-s-user')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/servicios-dashboard';
                }),
                      Action::make('pagos')
                ->label('Pagos Depósito')
                ->icon('heroicon-s-user')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/pagos';
                }),
            Action::make('mapa')
                ->label('Mapa Taxis')
                ->icon('icon-taxi')
                ->color('primary')
                ->url(function () {
                    // Extraer tenant ID de la URL actual
                    $currentUrl = request()->url();
                    $tenantId = '1'; // fallback

                    // Buscar el patrón /app/team/{tenant}/ en la URL actual
                    if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                        $tenantId = $matches[1];
                    }

                    return '/app/team/' . $tenantId . '/map-page';
                }),
        ];
    }
    protected function getTableFiltersFormWidth(): string
    {
        return 'xs';
    }

    public function getTabs(): array
    {
        /** @var object{total:int|string|null,taxistas:int|string|null,conductores:int|string|null,empleados:int|string|null} $counts */
        $counts = User::query()
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN role = 'taxista' THEN 1 ELSE 0 END) as taxistas")
            ->selectRaw("SUM(CASE WHEN role IN ('conductor', 'conductores') THEN 1 ELSE 0 END) as conductores")
            ->selectRaw("SUM(CASE WHEN role = 'empleado' THEN 1 ELSE 0 END) as empleados")
            ->first() ?? (object) [
                'total' => 0,
                'taxistas' => 0,
                'conductores' => 0,
                'empleados' => 0,
            ];

        return [
            'todos' => Tab::make('Todos')
                ->icon('heroicon-o-users')
                ->badge((string) ((int) ($counts->total ?? 0))),

            'taxistas' => Tab::make('Taxistas')
                ->icon('heroicon-o-truck')
                ->badge((string) ((int) ($counts->taxistas ?? 0)))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', 'taxista')),

            'conductores' => Tab::make('Conductores')
                ->icon('heroicon-o-identification')
                ->badge((string) ((int) ($counts->conductores ?? 0)))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereIn('role', ['conductor', 'conductores'])),

            'empleados' => Tab::make('Empleados')
                ->icon('heroicon-o-briefcase')
                ->badge((string) ((int) ($counts->empleados ?? 0)))
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', 'empleado')),
 'sin_licencia' => Tab::make()
                ->label('Sin licencia')
                ->badge((int) Taxista::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->whereNull('licencia')
                                ->orWhere('licencia', '');
                        })
                        ->distinct()
                        ->count('id'))
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                    return $nestedQuery
                        ->whereNull('licencia')
                        ->orWhere('licencia', '');
                })),
'con_licencia' => Tab::make()
                ->label('Con licencia')
                ->badge((int) Taxista::query()
                        ->whereNotNull('licencia')
                        ->where('licencia', '!=', '')
                        ->distinct()
                        ->count('id'))
                ->badgeColor('success')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereNotNull('licencia')),
                            'sin_licencia' => Tab::make()
                ->label('Sin licencia')
                ->badge((int) Taxista::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->whereNull('licencia')
                                ->orWhere('licencia', '');
                        })
                        ->distinct()
                        ->count('id'))
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                    return $nestedQuery
                        ->whereNull('licencia')
                        ->orWhere('licencia', '');
                })),
            'sin_nif' => Tab::make()
                ->label('Sin NIF')
                ->badge((int) Taxista::query()
                        ->where(function (Builder $query): Builder {
                            return $query
                                ->whereNull('nif')
                                ->orWhere('nif', '');
                        })
                        ->distinct()
                        ->count('id'))
                ->badgeColor('warning')
                ->icon('heroicon-m-identification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                    return $nestedQuery
                        ->whereNull('nif')
                        ->orWhere('nif', '');
                })),
                'sin_taxista' => Tab::make()
                ->label('Sin taxista')
                ->badge((int) Taxista::query()
                        ->whereNull('taxista_id')
                        ->distinct()
                        ->count('id'))
                ->badgeColor('danger')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('taxista_id')),
                           'con_taxista' => Tab::make()
                ->label('Con taxista')
                ->badge((int) Taxista::query()
                        ->whereNotNull('taxista_id')
                        ->distinct()
                        ->count('id'))
                ->badgeColor('success')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNotNull('taxista_id')),
           
                ];
    }
}
