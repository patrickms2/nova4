<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\TaxistaAppointment;
use App\Models\Taxista;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use Archilex\AdvancedTables\AdvancedTables;
use Illuminate\Support\Facades\Cache;

class ListTaxistas extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = TaxistaResource::class;

    protected static ?string $title = 'Taxistas';

    /**
     * @var array<string, string>
     */
    protected $listeners = [
        'echo:taxistas,.taxista.presence.updated' => '$refresh',
        'echo:taxistas,.taxista.location.updated' => '$refresh',
    ];

    public function getTabs(): array
    {
        $counts = $this->getTabCounts();

        return [
            'all' => Tab::make()
                ->label('Todo')
                ->badge($counts['all']),

            'active' => Tab::make()
                ->label('Activos')
                ->badge($counts['active'])
                ->badgeColor('success')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 1)),
'con_licencia' => Tab::make()
                ->label('Con licencia')
                ->badge($counts['with_licencia'])
                ->badgeColor('success')
                ->icon('heroicon-m-check-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereNotNull('licencia')),
            'sin_licencia' => Tab::make()
                ->label('Sin licencia')
                ->badge($counts['without_licencia'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                    return $nestedQuery
                        ->whereNull('licencia')
                        ->orWhere('licencia', '');
                })),
            'sin_nif' => Tab::make()
                ->label('Sin NIF')
                ->badge($counts['without_nif'])
                ->badgeColor('warning')
                ->icon('heroicon-m-identification')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where(function (Builder $nestedQuery): Builder {
                    return $nestedQuery
                        ->whereNull('nif')
                        ->orWhere('nif', '');
                })),
            'sin_municipio' => Tab::make()
                ->label('Sin municipio')
                ->badge($counts['without_municipio'])
                ->badgeColor('warning')
                ->icon('heroicon-m-map-pin')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->whereNull('municipio_id')),
            'inactive' => Tab::make()
                ->label('Inactivos')
                ->badge($counts['inactive'])
                ->badgeColor('gray')
                ->icon('heroicon-m-x-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('status', 0)),

            'with_taxis' => Tab::make()
                ->label('Con taxis')
                ->badge($counts['with_taxis'])
                ->badgeColor('info')
                ->icon('heroicon-m-truck')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereHas('taxis')),

            'with_appointments' => Tab::make()
                ->label('Con citas')
                ->badge($counts['with_appointments'])
                ->badgeColor('info')
                ->icon('heroicon-m-calendar-days')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereHas('appointments')),

            'without_appointments' => Tab::make()
                ->label('Sin citas')
                ->badge($counts['without_appointments'])
                ->badgeColor('gray')
                ->icon('heroicon-m-calendar')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDoesntHave('appointments')),

            'with_tickets' => Tab::make()
                ->label('Con tickets')
                ->badge($counts['with_tickets'])
                ->badgeColor('warning')
                ->icon('heroicon-m-ticket')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereHas('tickets')),

            'open_tickets' => Tab::make()
                ->label('Tickets abiertos')
                ->badge($counts['open_tickets'])
                ->badgeColor('danger')
                ->icon('heroicon-m-exclamation-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereHas('tickets', fn(Builder $ticketQuery): Builder => $ticketQuery->whereIn('status', ['abierto', 'en_proceso']))),

            'without_tickets' => Tab::make()
                ->label('Sin tickets')
                ->badge($counts['without_tickets'])
                ->badgeColor('gray')
                ->icon('heroicon-m-ticket')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDoesntHave('tickets')),

            'new_today' => Tab::make()
                ->label('Nuevos hoy')
                ->badge($counts['new_today'])
                ->badgeColor('warning')
                ->icon('heroicon-m-plus-circle')
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->whereDate('created_at', today())),
        ];
    }

    /**
     * @return array{
     *     all:int,
     *     active:int,
     *     inactive:int,
     *     with_taxis:int,
     *     with_appointments:int,
     *     without_appointments:int,
     *     with_licencia:int,
     *     without_licencia:int,
     *     without_nif:int,
     *     without_municipio:int,
     *     with_tickets:int,
     *     open_tickets:int,
     *     without_tickets:int,
     *     new_today:int
     * }
     */
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
            CreateAction::make()
                ->label('Nuevo Taxista')
                ->icon('heroicon-m-plus-circle')
                ->color('primary'),
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

    protected function getHeaderWidgets(): array
    {
        return TaxistaResource::getWidgets();
    }
}
