<?php

namespace App\Filament\App\Resources\Taxistas\Schemas;

use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Filament\Portal\Pages\TaxistaTracking;
use App\Models\Taxista;
use App\Models\TaxistaAppointment;
use App\Models\Taxi\Device as TaxiDevice;
use App\Models\TaxistaTicket;
use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema as DbSchema;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\Pages\EditTaxistaTicket;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
class TaxistaInfolist
{
    public static function resolveLicense(Taxista $record): string
    {
        $attributes = $record->getAttributes();

        if (array_key_exists('usuario_id', $attributes)) {
            $legacyLicense = $record->legacyUsuario?->licencia;

            if (filled($legacyLicense)) {
                return (string) $legacyLicense;
            }
        }

        return (string) ($record->licencia ?? '-');
    }

    public static function globalSearchDetails(Taxista $record): array
    {
        $details = [
            'Licencia' => self::resolveLicense($record),
            'Teléfono' => (string) ($record->phone ?: 'N/A'),
        ];

        $taxiPlates = $record->relationLoaded('taxis')
            ? $record->taxis
                ->pluck('license_plate')
                ->filter(fn (mixed $plate): bool => filled($plate))
                ->take(3)
                ->implode(', ')
            : $record->taxis()
                ->whereNotNull('license_plate')
                ->limit(3)
                ->pluck('license_plate')
                ->implode(', ');

        $driverNames = $record->relationLoaded('conductores')
            ? $record->conductores
                ->pluck('name')
                ->filter(fn (mixed $name): bool => filled($name))
                ->take(3)
                ->implode(', ')
            : $record->conductores()
                ->whereNotNull('name')
                ->limit(3)
                ->pluck('name')
                ->implode(', ');

        $summaryCounts = [
            'Taxis' => $record->taxis_count ?? $record->taxis()->count(),
            'Conductores' => $record->conductores_count ?? $record->conductores()->count(),
            'Citas' => $record->appointments_count ?? $record->appointments()->count(),
            'Documentos' => $record->documents_count ?? $record->documents()->count(),
            'Tickets' => $record->tickets_count ?? $record->tickets()->count(),
        ];

        foreach ($summaryCounts as $label => $count) {
            if ((int) $count > 0) {
                $details[$label] = (int) $count;
            }
        }

        if ($taxiPlates !== '') {
            $details['Matrículas'] = $taxiPlates;
        }

        if ($driverNames !== '') {
            $details['Conductores'] = $driverNames;
        }

        return $details;
    }

    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(fn ($record): string => 'Navegación rápida — ' . ($record->name ?? 'Taxista'))
                    ->description('Accede a las secciones del taxista y accesos generales.')
                    ->columnSpanFull()
                    ->icon('heroicon-o-squares-2x2')
                    ->schema([
                        \Filament\Infolists\Components\ViewEntry::make('nav_grid')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.app.resources.taxistas.partials.taxista-nav-grid'),
                    ]),

                Section::make('Perfil')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])->schema([
                            TextEntry::make('name')
                                ->label('Nombre')
                                ->getStateUsing(fn($record): string => trim(($record->name_last ?? '') . ' ' . ($record->name_first ?? '')) ?: (string)($record->name ?? '-')),
                            TextEntry::make('nif')
                                ->label('NIF')
                                ->placeholder('-')
                                ->copyable()
                                ->copyMessage('NIF copiado')
                                ->copyMessageDuration(1500),
                            TextEntry::make('phone')
                                ->label('Telefono')
                                ->url(fn($record): ?string => filled($record->phone) ? 'tel:' . preg_replace('/\s+/', '', (string)$record->phone) : null),
                            TextEntry::make('email')
                                ->label('Email')
                                ->url(fn($record): ?string => filled($record->email) ? 'mailto:' . (string)$record->email : null),
                            TextEntry::make('municipio_nombre')
                                ->label('Municipio')
                                ->getStateUsing(fn($record): string => (string)($record->municipio?->nombre ?? '-')),
                            TextEntry::make('licencia')
                                ->label('Licencia')
                                ->getStateUsing(fn($record): string => self::resolveLicense($record)),
                            TextEntry::make('type.label')->label('Tipo')->badge(),
                            TextEntry::make('role')->label('Rol')->badge(),
                            IconEntry::make('status')->label('Activo')->boolean(),
                            IconEntry::make('is_featured')->label('Destacado')->boolean(),
                            TextEntry::make('updated_at')
                                ->label('Ultima actividad')
                                ->since()
                                ->placeholder('-'),
                        ])->columnSpanFull(),
                    ])
                    ->columns(2),

                Section::make('Resumen')
                    ->schema([
                        TextEntry::make('taxis_count')
                            ->counts('taxis')
                            ->label('Taxis'),
                        TextEntry::make('appointments_count')
                            ->counts('appointments')
                            ->label('Citas'),
                        TextEntry::make('documents_count')
                            ->counts('documents')
                            ->label('Documentos'),
                        TextEntry::make('tickets_count')
                            ->counts('tickets')
                            ->label('Tickets'),
                    ])
                    ->columns(4),

                Section::make('Seguimiento de taxis')
                    ->description('Estado de seguimiento, completitud de datos y acceso rápido al mapa por taxi.')
                    ->schema([
                        TextEntry::make('tracking_health')
                            ->label('Estado')
                            ->badge()
                            ->state(function ($record): string {
                                $hasTrackingUuid = DbSchema::hasColumn('taxista_taxis', 'tracking_uuid');
                                $hasTrackingMode = DbSchema::hasColumn('taxista_taxis', 'tracking_mode');
                                $hasAnyTaxi = $record->taxis()->exists();

                                if (! $hasAnyTaxi) {
                                    return 'Sin taxis';
                                }

                                $query = $record->taxis();

                                if ($hasTrackingUuid) {
                                    $query->whereNotNull('tracking_uuid');
                                }

                                if ($hasTrackingMode) {
                                    $query->whereIn('tracking_mode', ['real', 'simulated']);
                                }

                                $query
                                    ->whereNotNull('current_lat')
                                    ->whereNotNull('current_lng')
                                    ->whereNotNull('last_located_at');

                                $complete = $query->exists();

                                return $complete ? 'OK' : 'Faltan datos';
                            })
                            ->color(function (string $state): string {
                                return match ($state) {
                                    'OK' => 'success',
                                    'Sin taxis' => 'warning',
                                    default => 'danger',
                                };
                            }),

                        Grid::make([
                            'default' => 1,
                            'md' => 4,
                        ])->schema([
                            TextEntry::make('tracking_taxis_total')
                                ->label('Total taxis')
                                ->state(function ($record): int {
                                    return (int) $record->taxis()->count();
                                }),

                            TextEntry::make('tracking_taxis_active')
                                ->label('Seguimiento activo')
                                ->badge()
                                ->color('success')
                                ->state(function ($record): int {
                                    if (! DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
                                        return 0;
                                    }

                                    return (int) $record->taxis()
                                        ->whereIn('tracking_mode', ['real', 'simulated'])
                                        ->count();
                                }),

                            TextEntry::make('tracking_taxis_inactive')
                                ->label('Seguimiento inactivo')
                                ->badge()
                                ->color('warning')
                                ->state(function ($record): int {
                                    if (! DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
                                        return (int) $record->taxis()->count();
                                    }

                                    return (int) $record->taxis()
                                        ->where(function ($query): void {
                                            $query->whereNull('tracking_mode')->orWhere('tracking_mode', 'disabled');
                                        })
                                        ->count();
                                }),

                            TextEntry::make('tracking_taxis_complete')
                                ->label('Datos completos')
                                ->badge()
                                ->color('info')
                                ->state(function ($record): int {
                                    $hasTrackingUuid = DbSchema::hasColumn('taxista_taxis', 'tracking_uuid');

                                    return (int) $record->taxis()
                                        ->when($hasTrackingUuid, fn ($query) => $query->whereNotNull('tracking_uuid'))
                                        ->whereNotNull('current_lat')
                                        ->whereNotNull('current_lng')
                                        ->whereNotNull('last_located_at')
                                        ->count();
                                }),
                        ]),

                        TextEntry::make('tracking_last_location')
                            ->label('Ultima ubicacion reportada')
                            ->state(function ($record): string {
                                $latest = $record->taxis()
                                    ->whereNotNull('last_located_at')
                                    ->latest('last_located_at')
                                    ->first(['last_located_at', 'license_plate']);

                                if (! $latest || ! $latest->last_located_at) {
                                    return 'Sin ubicaciones reportadas';
                                }

                                $timestamp = Carbon::parse($latest->last_located_at)->format('d/m/Y H:i');

                                return sprintf('%s • %s', (string) ($latest->license_plate ?? 'Taxi'), $timestamp);
                            })
                            ->color('gray'),

                        RepeatableEntry::make('tracking_taxis_detail')
                            ->label('Seguimiento por taxi')
                            ->state(function ($record): array {
                                $hasTrackingUuid = DbSchema::hasColumn('taxista_taxis', 'tracking_uuid');
                                $hasTrackingMode = DbSchema::hasColumn('taxista_taxis', 'tracking_mode');
                                $hasTrackingSimulation = DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled');
                                $traccarMapBaseUrl = self::resolveTraccarMapBaseUrl();

                                /** @var Collection<int, mixed> $taxis */
                                $taxis = $record->taxis()
                                    ->orderBy('license_plate')
                                    ->get();

                                $trackingUuidValues = $taxis
                                    ->pluck('tracking_uuid')
                                    ->filter(fn ($value): bool => filled($value))
                                    ->map(fn ($value): string => trim((string) $value))
                                    ->values()
                                    ->all();

                                $validatedByUuid = [];
                                if ($trackingUuidValues !== [] && DbSchema::hasTable('devices')) {
                                    $validatedByUuid = TaxiDevice::query()
                                        ->whereIn('unique_id', $trackingUuidValues)
                                        ->pluck('traccar_id', 'unique_id')
                                        ->map(fn ($value): bool => filled($value))
                                        ->all();
                                }

                                return $taxis->values()->map(function ($taxi, int $index) use ($hasTrackingUuid, $hasTrackingMode, $hasTrackingSimulation, $traccarMapBaseUrl, $validatedByUuid): array {
                                    $lat = $taxi->current_lat;
                                    $lng = $taxi->current_lng;
                                    $hasCoordinates = filled($lat) && filled($lng);
                                    $attributes = $taxi->getAttributes();

                                    $trackingUuid = $hasTrackingUuid && array_key_exists('tracking_uuid', $attributes) ? ((string) ($attributes['tracking_uuid'] ?? '')) : '';
                                    $trackingMode = $hasTrackingMode && array_key_exists('tracking_mode', $attributes) ? (string) ($attributes['tracking_mode'] ?? 'disabled') : 'disabled';
                                    $trackingEnabled = $trackingMode !== 'disabled';
                                    $lastLocatedAt = $taxi->last_located_at
                                        ? Carbon::parse($taxi->last_located_at)->format('d/m/Y H:i')
                                        : null;

                                    $missing = [];
                                    if ($hasTrackingUuid && blank($trackingUuid)) {
                                        $missing[] = 'codigo tracking';
                                    }
                                    if ($hasTrackingMode && ($trackingMode === '' || $trackingMode === 'disabled')) {
                                        $missing[] = 'modo';
                                    }
                                    if (! $hasCoordinates) {
                                        $missing[] = 'coordenadas';
                                    }
                                    if (blank($lastLocatedAt)) {
                                        $missing[] = 'ultima ubicacion';
                                    }

                                    $normalizedTrackingUuid = trim($trackingUuid);
                                    $traccarValidated = $normalizedTrackingUuid !== ''
                                        ? (bool) ($validatedByUuid[$normalizedTrackingUuid] ?? false)
                                        : false;

                                    return [
                                        'taxi_label' => sprintf('Taxi %d · %s', $index + 1, (string) ($taxi->license_plate ?? '-')),
                                        'license_plate' => (string) ($taxi->license_plate ?? '-'),
                                        'tracking_uuid' => $trackingUuid,
                                        'tracking_mode' => $trackingMode,
                                        'tracking_enabled' => $trackingEnabled,
                                        'traccar_validated' => $traccarValidated,
                                        'tracking_simulation_enabled' => $hasTrackingSimulation && array_key_exists('tracking_simulation_enabled', $attributes)
                                            ? (bool) ($attributes['tracking_simulation_enabled'] ?? false)
                                            : false,
                                        'last_located_at' => $lastLocatedAt ?? 'Sin ubicacion',
                                        'tracking_missing' => $missing === [] ? 'OK' : ('Falta: ' . implode(', ', $missing)),
                                        'portal_map_url' => TaxistaTracking::getUrl(['taxi' => (int) $taxi->id], panel: 'portal'),
                                        'traccar_map_url' => $traccarMapBaseUrl,
                                        'google_map_url' => $hasCoordinates ? sprintf('https://www.google.com/maps?q=%s,%s', $lat, $lng) : null,
                                    ];
                                })->values()->all();
                            })
                            ->schema([
                                TextEntry::make('taxi_label')
                                    ->label('Taxi')
                                    ->badge()
                                    ->color('info')
                                    ->weight('semibold'),
                                TextEntry::make('tracking_enabled')
                                    ->label('Seguimiento')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'ON' : 'OFF')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                                TextEntry::make('traccar_validated')
                                    ->label('Traccar')
                                    ->badge()
                                    ->formatStateUsing(fn (bool $state): string => $state ? 'Validado' : 'Pendiente')
                                    ->color(fn (bool $state): string => $state ? 'success' : 'warning'),
                                TextEntry::make('tracking_missing')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => $state === 'OK' ? 'success' : 'warning'),
                                TextEntry::make('tracking_uuid')
                                    ->label('UUID')
                                    ->copyable()
                                    ->copyMessage('UUID copiado')
                                    ->copyMessageDuration(1500)
                                    ->placeholder('Sin codigo'),
                                TextEntry::make('last_located_at')
                                    ->label('Ultimo ping'),
                                TextEntry::make('portal_map_url')
                                    ->label('Mapa')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Ver en mapa' : 'Sin mapa')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null),
                                TextEntry::make('traccar_map_url')
                                    ->label('Traccar')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Abrir Traccar' : 'Sin enlace')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null),
                                TextEntry::make('google_map_url')
                                    ->label('Google Maps')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Abrir mapa' : 'Sin coordenadas')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null)
                                    ->openUrlInNewTab(),
                            ])
                            ->columns(3)
                            ->columnSpanFull(),
                    ]),

                Section::make('Calendario y pendientes')
                    ->description('Próximas citas y tickets pendientes del taxista.')
                    ->schema([
                        RepeatableEntry::make('appointments_calendar')
                            ->label('Calendario de citas')
                            ->state(function ($record): array {
                                if (! DbSchema::hasTable('taxista_appointments')) {
                                    return [];
                                }

                                $taxistaRecord = $record;

                                return TaxistaAppointment::query()
                                    ->where('taxista_user_id', (int) $record->id)
                                    ->whereNotNull('starts_at')
                                    ->where('starts_at', '>=', now()->startOfDay())
                                    ->orderBy('starts_at')
                                    ->limit(8)
                                    ->get(['id', 'title', 'status', 'starts_at', 'ends_at'])
                                    ->map(function (TaxistaAppointment $appointment) use ($taxistaRecord): array {
                                        $startsAt = $appointment->starts_at ? Carbon::parse($appointment->starts_at)->format('d/m/Y H:i') : 'Sin fecha';
                                        $endsAt = $appointment->ends_at ? Carbon::parse($appointment->ends_at)->format('H:i') : null;

                                        return [
                                            'title' => (string) ($appointment->title ?: 'Cita'),
                                            'status' => self::appointmentStatusLabel((string) ($appointment->status ?? 'pendiente')),
                                            'starts_at' => $endsAt ? $startsAt.' - '.$endsAt : $startsAt,
                                            'url' => TaxistaResource::getUrl(
                                                'citas',
                                                ['record' => $taxistaRecord],
                                                tenant: Filament::getTenant(),
                                            ),
                                        ];
                                    })
                                    ->all();
                            })
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Cita')
                                    ->weight('semibold'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => self::appointmentStatusColor($state)),
                                TextEntry::make('starts_at')
                                    ->label('Horario'),
                                TextEntry::make('url')
                                    ->label('Abrir')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Ver cita' : '-')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),

                        RepeatableEntry::make('pending_tickets')
                            ->label('Tickets pendientes')
                            ->state(function ($record): array {
                                if (! DbSchema::hasTable('taxista_tickets')) {
                                    return [];
                                }

                                $taxistaRecord = $record;

                                return TaxistaTicket::query()
                                    ->where('user_id', (int) $record->id)
                                    ->whereIn('status', ['abierto', 'en_proceso'])
                                    ->orderByDesc('opened_at')
                                    ->limit(8)
                                    ->get(['id', 'title', 'status', 'priority', 'opened_at'])
                                    ->map(function (TaxistaTicket $ticket) use ($taxistaRecord): array {
                                        return [
                                            'title' => (string) ($ticket->title ?: 'Ticket'),
                                            'status' => self::ticketStatusLabel((string) ($ticket->status ?? 'abierto')),
                                            'priority' => ucfirst((string) ($ticket->priority ?? 'media')),
                                            'opened_at' => $ticket->opened_at ? Carbon::parse($ticket->opened_at)->format('d/m/Y H:i') : '-',
                                            'url' => TaxistaResource::getUrl(
                                                'tickets',
                                                ['record' => $taxistaRecord],
                                                tenant: Filament::getTenant(),
                                            ),
                                        ];
                                    })
                                    ->all();
                            })
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Ticket')
                                    ->weight('semibold'),
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->color(fn (string $state): string => self::ticketStatusColor($state)),
                                TextEntry::make('priority')
                                    ->label('Prioridad')
                                    ->badge()
                                    ->color(fn (string $state): string => strtolower($state) === 'alta' ? 'danger' : 'warning'),
                                TextEntry::make('opened_at')
                                    ->label('Abierto'),
                                TextEntry::make('url')
                                    ->label('Abrir')
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? 'Ver ticket' : '-')
                                    ->url(fn (?string $state): ?string => filled($state) ? $state : null),
                            ])
                            ->columns(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function appointmentStatusLabel(string $status): string
    {
        return match ($status) {
            'pendiente' => 'Pendiente',
            'confirmada' => 'Confirmada',
            'finalizada' => 'Finalizada',
            'cancelada' => 'Cancelada',
            default => ucfirst($status),
        };
    }

    public static function appointmentStatusColor(string $statusLabel): string
    {
        return match ($statusLabel) {
            'Confirmada' => 'success',
            'Finalizada' => 'info',
            'Cancelada' => 'danger',
            default => 'warning',
        };
    }

    public static function ticketStatusLabel(string $status): string
    {
        return match ($status) {
            'abierto' => 'Abierto',
            'en_proceso' => 'En proceso',
            'resuelto' => 'Resuelto',
            default => ucfirst($status),
        };
    }

    public static function ticketStatusColor(string $statusLabel): string
    {
        return match ($statusLabel) {
            'Abierto' => 'danger',
            'En proceso' => 'warning',
            'Resuelto' => 'success',
            default => 'gray',
        };
    }

    public static function resolveTraccarMapBaseUrl(): ?string
    {
        $apiUrl = (string) (config('traccar.url') ?: config('traccar.base_url') ?: '');

        if ($apiUrl === '') {
            return null;
        }

        return preg_replace('#/api/?$#', '', rtrim($apiUrl, '/'));
    }
}
