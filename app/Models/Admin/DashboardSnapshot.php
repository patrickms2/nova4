<?php

namespace App\Models\Admin;

use App\Support\Admin\AdminResourceRegistry;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DashboardSnapshot
{
    private const READY_STATUSES = ['active', 'available', 'confirmed', 'paid', 'verified', 'completed', 'issued'];
    private const ATTENTION_STATUSES = ['draft', 'pending', 'reserved', 'in_progress', 'paid_pending_provider_confirmation'];
    private const RISK_STATUSES = ['cancelled', 'unavailable', 'failed', 'overdue', 'refunded', 'low_stock'];
    private const CLOSED_STATUSES = ['paid', 'completed', 'cancelled', 'refunded', 'archived'];
    private const REVENUE_STATUSES = ['paid', 'verified', 'completed', 'confirmed', 'issued', 'paid_pending_provider_confirmation'];

    private const RESERVATION_RESOURCES = [
        'concierge_bookings',
        'villa_reservations',
        'taxi_reservations',
        'restaurant_reservations',
        'winery_visits',
        'tour_reservations',
        'shop_orders',
        'itineraries',
    ];

    private const SCHEDULE_FIELDS = [
        'concierge_bookings' => 'travel_date',
        'villa_reservations' => 'check_in',
        'taxi_reservations' => 'pickup_at',
        'restaurant_reservations' => 'reserved_at',
        'winery_visits' => 'visit_at',
        'tour_reservations' => 'tour_date',
        'shop_orders' => 'ordered_at',
        'itineraries' => 'start_date',
        'itinerary_services' => 'service_date',
    ];

    public function __construct(private readonly array $catalog)
    {
    }

    public static function make(): self
    {
        return new self(AdminResourceRegistry::all());
    }

    public function toArray(): array
    {
        $schemaReady = true;
        $schemaMessage = 'Tablas ERP sincronizadas.';

        try {
            AdminResourceRegistry::ensureSchema();
        } catch (Throwable) {
            $schemaReady = false;
            $schemaMessage = 'No se pudo sincronizar el esquema. Revisa conexión MySQL.';
        }

        $groups = $this->groupSummaries();
        $totalRecords = array_sum(array_column($groups, 'total'));
        $readyRecords = array_sum(array_column($groups, 'ready'));
        $pendingReservations = $this->pendingReservations();
        $upcomingAgenda = $this->upcomingAgenda();
        $paidRevenue = $this->sumResource('payments', 'amount', self::REVENUE_STATUSES);
        $openInvoices = $this->countByStatus('invoices', ['draft', 'issued', 'pending', 'overdue']);
        $openInvoiceAmount = $this->sumResource('invoices', 'total_amount', ['draft', 'issued', 'pending', 'overdue']);
        $activity24h = $this->safeInt(fn () => DB::table('admin_audit_log')->where('created_at', '>=', now('UTC')->subDay()->toDateTimeString())->count());

        $metrics = [
            DashboardMetric::make('Módulos ERP', $this->number(count($this->catalog)), $this->number(count($groups)) . ' áreas operativas', 'blue', '/admin/', '▦'),
            DashboardMetric::make('Registros totales', $this->number($totalRecords), 'Datos vivos en tablas admin_*', 'neutral', '/admin/', '∑'),
            DashboardMetric::make('Operativos', $this->number($readyRecords), 'Activos, confirmados o completados', 'green', '/admin/', '✓'),
            DashboardMetric::make('En gestión', $this->number($pendingReservations), 'Reservas/pedidos no cerrados', $pendingReservations > 0 ? 'amber' : 'green', $this->firstStatusRoute(self::RESERVATION_RESOURCES, self::ATTENTION_STATUSES, 'villa_reservations'), '◌'),
            DashboardMetric::make('Agenda 7 días', $this->number($upcomingAgenda), 'Servicios próximos no cancelados', 'blue', '/admin/', '◷'),
            DashboardMetric::make('Ingresos cobrados', $this->currency($paidRevenue), 'Pagos verificados/completados', 'green', '/admin/payments/?status=paid', '€'),
            DashboardMetric::make('Facturas abiertas', $this->number($openInvoices), $this->currency($openInvoiceAmount) . ' pendiente/revisable', $openInvoices > 0 ? 'amber' : 'green', '/admin/invoices/?status=pending', '№'),
            DashboardMetric::make('Actividad 24h', $this->number($activity24h), 'Eventos auditados recientes', 'neutral', '/admin/', '↺'),
        ];

        return [
            'health' => [
                'ok' => $schemaReady,
                'message' => $schemaMessage,
                'generated_at' => now('UTC')->toDateTimeString(),
            ],
            'metrics' => array_map(fn (DashboardMetric $metric) => $metric->toArray(), $metrics),
            'groups' => $groups,
            'alerts' => $this->alerts($schemaReady, $schemaMessage),
            'activity' => $this->recentActivity(),
            'quickLinks' => $this->quickLinks(),
        ];
    }

    private function groupSummaries(): array
    {
        $groups = [];

        foreach (AdminResourceRegistry::grouped() as $groupName => $resources) {
            $groupResources = [];
            $total = 0;
            $ready = 0;
            $attention = 0;
            $risk = 0;
            $amount = 0.0;

            foreach ($resources as $key => $config) {
                $resourceTotal = $this->countResource($key);
                $resourceReady = $this->countByStatus($key, self::READY_STATUSES);
                $resourceAttention = $this->countByStatus($key, self::ATTENTION_STATUSES);
                $resourceRisk = $this->countByStatus($key, self::RISK_STATUSES);
                $moneyField = $this->moneyField($config);
                $resourceAmount = $moneyField ? $this->sumResource($key, $moneyField, self::REVENUE_STATUSES) : 0.0;

                $total += $resourceTotal;
                $ready += $resourceReady;
                $attention += $resourceAttention;
                $risk += $resourceRisk;
                $amount += $resourceAmount;

                $groupResources[] = [
                    'key' => $key,
                    'label' => (string) ($config['label'] ?? $key),
                    'icon' => (string) ($config['icon'] ?? '•'),
                    'description' => (string) ($config['description'] ?? ''),
                    'total' => $resourceTotal,
                    'ready' => $resourceReady,
                    'attention' => $resourceAttention,
                    'risk' => $resourceRisk,
                    'amount' => $resourceAmount > 0 ? $this->currency($resourceAmount) : '',
                    'href' => $this->routeFor($key),
                ];
            }

            $groups[] = [
                'name' => (string) $groupName,
                'total' => $total,
                'ready' => $ready,
                'attention' => $attention,
                'risk' => $risk,
                'amount' => $amount > 0 ? $this->currency($amount) : '',
                'tone' => $risk > 0 ? 'red' : ($attention > 0 ? 'amber' : ($ready > 0 ? 'green' : 'neutral')),
                'resources' => $groupResources,
            ];
        }

        return $groups;
    }

    private function alerts(bool $schemaReady, string $schemaMessage): array
    {
        $alerts = [];

        if (! $schemaReady) {
            $alerts[] = DashboardAlert::make('Conexión de datos', 'Error', $schemaMessage, 'red', '/healthz', 'Ver healthz', '!');
        }

        $pendingReservations = $this->pendingReservations();
        $overdueInvoices = $this->overdueInvoices();
        $failedPayments = $this->countByStatus('payments', ['failed', 'refunded']);
        $lowStock = $this->lowStockProducts();

        $alerts[] = DashboardAlert::make(
            'Reservas por confirmar',
            $this->number($pendingReservations),
            $pendingReservations > 0 ? 'Prioriza pendientes, reservadas y en curso.' : 'No hay reservas bloqueadas ahora mismo.',
            $pendingReservations > 0 ? 'amber' : 'green',
            $this->firstStatusRoute(self::RESERVATION_RESOURCES, self::ATTENTION_STATUSES, 'villa_reservations'),
            'Revisar cola',
            '◌'
        );

        $alerts[] = DashboardAlert::make(
            'Facturas vencidas',
            $this->number($overdueInvoices),
            $overdueInvoices > 0 ? 'Requieren seguimiento financiero.' : 'Sin vencimientos críticos.',
            $overdueInvoices > 0 ? 'red' : 'green',
            '/admin/invoices/?status=overdue',
            'Abrir facturas',
            '№'
        );

        $alerts[] = DashboardAlert::make(
            'Pagos fallidos/devueltos',
            $this->number($failedPayments),
            $failedPayments > 0 ? 'Reintento o revisión manual recomendada.' : 'Pasarela sin incidencias registradas.',
            $failedPayments > 0 ? 'red' : 'green',
            '/admin/payments/?status=failed',
            'Abrir pagos',
            '€'
        );

        $alerts[] = DashboardAlert::make(
            'Stock bajo',
            $this->number($lowStock),
            $lowStock > 0 ? 'Productos con estado low_stock o ≤ 5 unidades.' : 'Inventario sin mínimos críticos.',
            $lowStock > 0 ? 'amber' : 'green',
            '/admin/shop-products/?status=low_stock',
            'Abrir tienda',
            '□'
        );

        return array_map(fn (DashboardAlert $alert) => $alert->toArray(), $alerts);
    }

    private function recentActivity(): array
    {
        return $this->safeArray(function () {
            return DB::table('admin_audit_log')
                ->select(['actor_name', 'actor_role', 'action', 'resource', 'summary', 'created_at'])
                ->orderByDesc('created_at')
                ->limit(8)
                ->get()
                ->map(function ($row) {
                    $resource = (string) ($row->resource ?? '');

                    return [
                        'actor' => (string) ($row->actor_name ?: 'Sistema'),
                        'role' => (string) ($row->actor_role ?: 'operator'),
                        'action' => (string) ($row->action ?: 'evento'),
                        'resource' => $resource !== '' && isset($this->catalog[$resource]) ? (string) $this->catalog[$resource]['label'] : 'Admin',
                        'summary' => (string) ($row->summary ?: 'Evento auditado'),
                        'time' => $row->created_at ? date('d/m H:i', strtotime((string) $row->created_at)) : '—',
                        'href' => $resource !== '' && isset($this->catalog[$resource]) ? $this->routeFor($resource) : '/admin/',
                    ];
                })
                ->all();
        });
    }

    private function quickLinks(): array
    {
        $links = [];
        foreach (['concierge_bookings', 'villa_reservations', 'taxi_reservations', 'restaurant_reservations', 'shop_orders', 'payments', 'invoices'] as $resource) {
            if (! isset($this->catalog[$resource])) {
                continue;
            }

            $links[] = [
                'label' => (string) $this->catalog[$resource]['label'],
                'icon' => (string) ($this->catalog[$resource]['icon'] ?? '•'),
                'href' => $this->routeFor($resource),
            ];
        }

        return $links;
    }

    private function pendingReservations(): int
    {
        return array_sum(array_map(fn (string $resource) => $this->countByStatus($resource, self::ATTENTION_STATUSES), self::RESERVATION_RESOURCES));
    }

    private function upcomingAgenda(): int
    {
        $count = 0;
        foreach (self::SCHEDULE_FIELDS as $resource => $field) {
            $count += $this->countUpcoming($resource, $field);
        }

        return $count;
    }

    private function overdueInvoices(): int
    {
        if (! isset($this->catalog['invoices'])) {
            return 0;
        }

        return $this->safeInt(function () {
            return DB::table($this->catalog['invoices']['table'])
                ->where(function ($query) {
                    $query->where('status', 'overdue')
                        ->orWhere(function ($query) {
                            $query->whereNotNull('due_at')
                                ->where('due_at', '<', now('UTC')->toDateString())
                                ->whereNotIn('status', self::CLOSED_STATUSES);
                        });
                })
                ->count();
        });
    }

    private function lowStockProducts(): int
    {
        if (! isset($this->catalog['shop_products'])) {
            return 0;
        }

        return $this->safeInt(function () {
            return DB::table($this->catalog['shop_products']['table'])
                ->where(function ($query) {
                    $query->where('status', 'low_stock')
                        ->orWhere('stock', '<=', 5);
                })
                ->whereNotIn('status', ['cancelled', 'archived'])
                ->count();
        });
    }

    private function countResource(string $resource): int
    {
        if (! isset($this->catalog[$resource])) {
            return 0;
        }

        return $this->safeInt(fn () => DB::table($this->catalog[$resource]['table'])->count());
    }

    private function countByStatus(string $resource, array $statuses): int
    {
        if (! isset($this->catalog[$resource]) || ! $this->hasField($resource, 'status')) {
            return 0;
        }

        return $this->safeInt(fn () => DB::table($this->catalog[$resource]['table'])->whereIn('status', $statuses)->count());
    }

    private function countUpcoming(string $resource, string $field): int
    {
        if (! isset($this->catalog[$resource]) || ! $this->hasField($resource, $field)) {
            return 0;
        }

        return $this->safeInt(function () use ($resource, $field) {
            $query = DB::table($this->catalog[$resource]['table'])
                ->whereNotNull($field)
                ->where($field, '>=', now('UTC')->startOfDay()->toDateTimeString())
                ->where($field, '<=', now('UTC')->addDays(7)->endOfDay()->toDateTimeString());

            if ($this->hasField($resource, 'status')) {
                $query->whereNotIn('status', ['cancelled', 'failed', 'refunded', 'archived']);
            }

            return $query->count();
        });
    }

    private function sumResource(string $resource, string $field, array $statuses = []): float
    {
        if (! isset($this->catalog[$resource]) || ! $this->hasField($resource, $field)) {
            return 0.0;
        }

        return $this->safeFloat(function () use ($resource, $field, $statuses) {
            $query = DB::table($this->catalog[$resource]['table']);

            if ($statuses !== [] && $this->hasField($resource, 'status')) {
                $query->whereIn('status', $statuses);
            }

            return $query->sum($field);
        });
    }

    private function firstStatusRoute(array $resources, array $statuses, string $fallbackResource): string
    {
        foreach ($resources as $resource) {
            if (! isset($this->catalog[$resource])) {
                continue;
            }

            foreach ($statuses as $status) {
                if ($this->countByStatus($resource, [$status]) > 0) {
                    return $this->routeFor($resource, 'status', $status);
                }
            }
        }

        return $this->routeFor($fallbackResource);
    }

    private function routeFor(string $resource, ?string $filter = null, ?string $value = null): string
    {
        if (! isset($this->catalog[$resource])) {
            return '/admin/';
        }

        $slug = (string) ($this->catalog[$resource]['slug'] ?? str_replace('_', '-', $resource));
        $url = '/admin/' . $slug . '/';

        if ($filter !== null && $filter !== '' && $value !== null && $value !== '') {
            $url .= '?' . http_build_query([$filter => $value]);
        }

        return $url;
    }

    private function moneyField(array $config): ?string
    {
        foreach ($config['fields'] ?? [] as $field) {
            if (($field['type'] ?? '') === 'money') {
                return (string) $field['key'];
            }
        }

        return null;
    }

    private function hasField(string $resource, string $field): bool
    {
        foreach (($this->catalog[$resource]['fields'] ?? []) as $definition) {
            if (($definition['key'] ?? null) === $field) {
                return true;
            }
        }

        return false;
    }

    private function number(int|float $value): string
    {
        return number_format((float) $value, 0, ',', '.');
    }

    private function currency(float $amount): string
    {
        return number_format($amount, 0, ',', '.') . ' €';
    }

    private function safeInt(callable $callback): int
    {
        try {
            return (int) $callback();
        } catch (Throwable) {
            return 0;
        }
    }

    private function safeFloat(callable $callback): float
    {
        try {
            return (float) $callback();
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function safeArray(callable $callback): array
    {
        try {
            return (array) $callback();
        } catch (Throwable) {
            return [];
        }
    }
}
