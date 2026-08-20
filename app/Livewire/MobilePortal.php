<?php

namespace App\Livewire;

use App\Events\TaxistaLocationUpdated;
use App\Events\TaxistaPresenceUpdated;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\App\Resources\TaxistaTaxis\Tables\TaxistaTaxisTable;
use App\Filament\App\Resources\TaxistaTaxis\TaxistaTaxiResource;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Filament\Portal\Pages\TaxistaPortal;
use App\Filament\Portal\Pages\TaxistaTracking;
use App\Models\EmployeeShift;
use App\Models\EmployeeTimeOff;
use App\Models\ShiftSwapRequest;
use App\Models\Taxi\Attendance;
use App\Models\Taxi\Device as TaxiDevice;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use App\Models\User;
use App\Services\TraccarService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MobilePortal extends Component
{
    public bool $isOnline = false;

    public int $unreadNotifications = 0;

    public int $unreadAnnouncements = 0;

    public bool $showNotifications = false;

    public bool $showAnnouncements = false;

    public string $announcementsAutoKey = '';

    /** @var array{id:int, title:string, content_html:?string, read:bool, starts_at:string}|null */
    public ?array $selectedAnnouncement = null;

    /** @var array<int, array{id:string, title:string, body:?string, read:bool, created_at:string}> */
    public array $notifications = [];

    /** @var array<int, array{id:int, title:string, excerpt:?string, content_html:?string, read:bool, starts_at:string}> */
    public array $announcements = [];

    public ?float $lastLat = null;

    public ?float $lastLng = null;

    public ?string $lastLocationAt = null;

    public ?array $selectedTicket = null;

    public bool $trackingActive = false;

    public bool $embedded = false;

    public bool $showTimeClockDropdown = false;

    public $identification = null;

    /** @var array<string, mixed> */
    private array $runtimeCache = [];

    public function mount(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            $this->redirect(route('filament.portal.auth.login'), navigate: true);

            return;
        }

        /** @var array<string, mixed> $attributes */
        $attributes = $authUser->getAttributes();

        /*$this->isOnline = DbSchema::hasColumn('users', 'is_online')
            ? (bool)($attributes['is_online'] ?? false)
            : false;

        $userId = (int)($authUser->getAuthIdentifier() ?? 0);

        $this->unreadNotifications = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'notifications:unread-count',
                [],
                static fn(): int => DbSchema::hasTable('notifications') && method_exists($authUser, 'unreadNotifications')
                    ? (int)$authUser->unreadNotifications()->count()
                    : 0,
                15,
            )
            : 0;

        $this->unreadAnnouncements = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'announcements:unread-count',
                [],
                static fn(): int => DbSchema::hasTable('announcements') && method_exists($authUser, 'unreadAnnouncements')
                    ? (int)$authUser->unreadAnnouncements()->count()
                    : 0,
                15,
            )
            : 0;

        $this->announcements = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'announcements:list',
                [],
                function () use ($authUser): array {
                    if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'recentAnnouncements') || ! method_exists($authUser, 'dismissedAnnouncements')) {
                        return [];
                    }

                    $readAnnouncementIds = $authUser->dismissedAnnouncements()
                        ->pluck('announcements.id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    return $authUser->recentAnnouncements()
                        ->limit(12)
                        ->get()
                        ->map(fn ($announcement) => $this->mapAnnouncement(
                            $announcement,
                            in_array((int) $announcement->getKey(), $readAnnouncementIds, true),
                        ))
                        ->all();
                },
                15,
            )
            : [];

        if (DbSchema::hasColumn('users', 'last_lat')) {
            $value = $attributes['last_lat'] ?? null;
            $this->lastLat = filled($value) ? (float)$value : null;
        }

        if (DbSchema::hasColumn('users', 'last_lng')) {
            $value = $attributes['last_lng'] ?? null;
            $this->lastLng = filled($value) ? (float)$value : null;
        }

        if (DbSchema::hasColumn('users', 'last_location_at')) {
            $value = $attributes['last_location_at'] ?? null;

            if (filled($value)) {
                $this->lastLocationAt = Carbon::parse((string)$value)->format('d/m/Y H:i');
            }
        }*/

        $this->syncAnnouncementsAutoKey();
        $this->loadNotifications();
    }

    #[On('notification-created')]
    public function refreshUnreadNotifications(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            $this->unreadNotifications = 0;

            return;
        }

        $userId = (int)($authUser->getAuthIdentifier() ?? 0);

        $this->flushMobilePortalCache($userId);

        $this->unreadNotifications = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'notifications:unread-count',
                [],
                static fn(): int => DbSchema::hasTable('notifications') && method_exists($authUser, 'unreadNotifications')
                    ? (int)$authUser->unreadNotifications()->count()
                    : 0,
                15,
            )
            : 0;

        $this->loadNotifications();

        $this->refreshUnreadAnnouncements();

        if ($this->showAnnouncements) {
            $this->loadAnnouncements();
        }
    }

    /**
     * @param array<string, mixed> $notification
     */
    #[On('portal-taxista-refresh')]
    public function handlePortalTaxistaRefresh(array $notification = []): void
    {
        $payload = isset($notification['notification']) && is_array($notification['notification'])
            ? $notification['notification']
            : $notification;

        $entity = strtolower(trim((string)($payload['taxista_entity'] ?? '')));
        $action = strtolower(trim((string)($payload['taxista_action'] ?? '')));

        if (! in_array($entity, ['appointment', 'document', 'ticket', 'timeoff', 'shift_swap'], true)) {
            return;
        }

        if (! in_array($action, ['', 'status_changed', 'updated', 'created', 'answered'], true)) {
            return;
        }

        $this->refreshUnreadNotifications();
        $this->refreshUnreadAnnouncements();

        if ($this->showAnnouncements) {
            $this->loadAnnouncements();
        }
    }

    public function toggleNotifications(): void
    {
        $this->showNotifications = !$this->showNotifications;

        if ($this->showNotifications) {
            $this->loadNotifications();
        }
    }

    public function closeNotifications(): void
    {
        $this->showNotifications = false;
    }

    public function toggleAnnouncements(): void
    {
        $this->showAnnouncements = ! $this->showAnnouncements;

        if ($this->showAnnouncements) {
            $this->loadAnnouncements();
        }
    }

    public function openAnnouncements(): void
    {
        $this->showAnnouncements = true;
        $this->selectedAnnouncement = null;
        $this->loadAnnouncements();
    }

    public function closeAnnouncements(): void
    {
        $this->showAnnouncements = false;
        $this->selectedAnnouncement = null;
    }

    public function backToAnnouncementsList(): void
    {
        $this->selectedAnnouncement = null;
    }

    public function openAnnouncement(int $announcementId): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (! $authUser instanceof Authenticatable) {
            return;
        }

        if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'recentAnnouncements')) {
            return;
        }

        $announcement = $authUser->recentAnnouncements()->find($announcementId);

        if (! $announcement) {
            return;
        }

        $wasUnread = method_exists($authUser, 'unreadAnnouncements')
            ? $authUser->unreadAnnouncements()->whereKey($announcementId)->exists()
            : false;

        if ($wasUnread && method_exists($authUser, 'markAnnouncementAsRead')) {
            $authUser->markAnnouncementAsRead($announcement);
            $this->flushMobilePortalCache((int) ($authUser->getAuthIdentifier() ?? 0));
            $this->refreshUnreadAnnouncements();
            $this->loadAnnouncements();
        }

        $this->selectedAnnouncement = $this->mapAnnouncement($announcement, true);
    }

    public function markAnnouncementAsRead(int $announcementId): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            return;
        }

        if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'unreadAnnouncements')) {
            return;
        }

        $announcement = $authUser->unreadAnnouncements()->find($announcementId);

        if (! $announcement) {
            return;
        }

        $authUser->markAnnouncementAsRead($announcement);

        $this->flushMobilePortalCache((int)($authUser->getAuthIdentifier() ?? 0));
        $this->refreshUnreadAnnouncements();
        $this->loadAnnouncements();
    }

    public function markAllAnnouncementsAsRead(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            return;
        }

        if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'unreadAnnouncements')) {
            return;
        }

        $authUser->unreadAnnouncements()->get()->each(fn($announcement) => $authUser->markAnnouncementAsRead($announcement));

        $this->flushMobilePortalCache((int)($authUser->getAuthIdentifier() ?? 0));
        $this->refreshUnreadAnnouncements();
        $this->loadAnnouncements();
    }

    private function loadAnnouncements(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            $this->announcements = [];

            return;
        }

        if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'recentAnnouncements') || ! method_exists($authUser, 'dismissedAnnouncements')) {
            $this->announcements = [];

            return;
        }

        $userId = (int)($authUser->getAuthIdentifier() ?? 0);

        $this->announcements = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'announcements:list',
                [],
                function () use ($authUser): array {
                    $readAnnouncementIds = $authUser->dismissed()
                        ->pluck('announcements.id')
                        ->map(static fn (mixed $id): int => (int) $id)
                        ->all();

                    return $authUser->recentAnnouncements()
                        ->limit(12)
                        ->get()
                        ->map(fn ($announcement) => $this->mapAnnouncement(
                            $announcement,
                            in_array((int) $announcement->getKey(), $readAnnouncementIds, true),
                        ))
                        ->all();
                },
                15,
            )
            : [];

        $this->syncAnnouncementsAutoKey();
    }

    public function refreshUnreadAnnouncements(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            $this->unreadAnnouncements = 0;

            return;
        }

        $userId = (int)($authUser->getAuthIdentifier() ?? 0);

        $this->flushMobilePortalCache($userId);

        $this->unreadAnnouncements = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'announcements:unread-count',
                [],
                static fn(): int => DbSchema::hasTable('announcements') && method_exists($authUser, 'unreadAnnouncements')
                    ? (int)$authUser->unreadAnnouncements()->count()
                    : 0,
                15,
            )
            : 0;

        $this->syncAnnouncementsAutoKey();
    }

    private function syncAnnouncementsAutoKey(): void
    {
        if ($this->unreadAnnouncements <= 0) {
            $this->announcementsAutoKey = '';

            return;
        }

        $authUser = auth()->user() ?? auth('web')->user();

        if (! $authUser instanceof Authenticatable) {
            $this->announcementsAutoKey = '';

            return;
        }

        if (! DbSchema::hasTable('announcements') || ! method_exists($authUser, 'unreadAnnouncements')) {
            $this->announcementsAutoKey = '';

            return;
        }

        $announcementIds = $authUser->unreadAnnouncements()
            ->limit(12)
            ->pluck('announcements.id')
            ->map(static fn (mixed $id): int => (int) $id)
            ->all();

        $this->announcementsAutoKey = sprintf(
            'portal:%s:%s',
            (string) $authUser->getAuthIdentifier(),
            implode('-', $announcementIds),
        );
    }

    /**
     * @return array{id:int, title:string, excerpt:?string, content_html:?string, read:bool, starts_at:string}
     */
    private function mapAnnouncement(object $announcement, bool $read = false): array
    {
        $excerpt = Str::of((string) ($announcement->content ?? ''))
            ->replaceMatches('/\s+/u', ' ')
            ->stripTags()
            ->trim()
            ->limit(140)
            ->value();

        return [
            'id' => (int) $announcement->getKey(),
            'title' => (string) $announcement->title,
            'excerpt' => $excerpt !== '' ? $excerpt : null,
            'content_html' => filled($announcement->content ?? null) ? (string) $announcement->content : null,
            'read' => $read,
            'starts_at' => (string) optional($announcement->starts_at)->format('d/m/Y'),
        ];
    }

    public function markNotificationAsRead(string $notificationId): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            return;
        }

        if (!DbSchema::hasTable('notifications') || !method_exists($authUser, 'notifications')) {
            return;
        }

        $notification = $authUser->notifications()->whereKey($notificationId)->first();

        if (!$notification) {
            return;
        }

        $notification->markAsRead();

        $payload = is_array($notification->data ?? null) ? $notification->data : [];

        $this->flushMobilePortalCache((int)($authUser->getAuthIdentifier() ?? 0));
        $this->refreshUnreadNotifications();

        $redirectUrl = $this->resolveNotificationRedirectUrl($payload);

        if ($redirectUrl) {
            $this->redirect($redirectUrl, navigate: true);
        }
    }

    public function markAllNotificationsAsRead(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            return;
        }

        if (!DbSchema::hasTable('notifications') || !method_exists($authUser, 'unreadNotifications')) {
            return;
        }

        $authUser->unreadNotifications->markAsRead();

        $this->flushMobilePortalCache((int)($authUser->getAuthIdentifier() ?? 0));
        $this->refreshUnreadNotifications();
    }

    private function loadNotifications(): void
    {
        $authUser = auth()->user() ?? auth('web')->user();

        if (!$authUser instanceof Authenticatable) {
            $this->notifications = [];

            return;
        }

        if (!DbSchema::hasTable('notifications') || !method_exists($authUser, 'notifications')) {
            $this->notifications = [];

            return;
        }

        $userId = (int)($authUser->getAuthIdentifier() ?? 0);

        $this->notifications = $userId > 0
            ? $this->rememberMobilePortalData(
                $userId,
                'notifications:list',
                ['limit' => 15],
                static function () use ($authUser): array {
                    return $authUser->notifications()
                        ->latest()
                        ->limit(15)
                        ->get()
                        ->map(static function ($notification): array {
                            $data = is_array($notification->data ?? null) ? $notification->data : [];

                            return [
                                'id' => (string)$notification->id,
                                'title' => (string)($data['title'] ?? $data['title'] ?? 'Notificación'),
                                'body' => isset($data['body']) ? (string)$data['body'] : (isset($data['message']) ? (string)$data['message'] : null),
                                'read' => filled($notification->read_at),
                                'entity' => isset($data['taxista_entity']) ? (string)$data['taxista_entity'] : null,
                                'action' => isset($data['taxista_action']) ? (string)$data['taxista_action'] : null,
                                'created_at' => $notification->created_at?->diffForHumans() ?? '',
                            ];
                        })
                        ->all();
                },
                15,
            )
            : [];
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function resolveNotificationRedirectUrl(array $payload): ?string
    {
        $entity = strtolower(trim((string)($payload['taxista_entity'] ?? '')));

        if ($entity === 'document') {
            return TaxistaPortal::getUrl(['tab' => 'documentos', 'docs' => 'home'], panel: 'portal');
        }

        if ($entity === 'appointment') {
            return TaxistaPortal::getUrl(['tab' => 'citas'], panel: 'portal');
        }

        if ($entity === 'ticket') {
            return TaxistaPortal::getUrl(['tab' => 'tickets'], panel: 'portal');
        }

        return null;
    }

    /**
     * @return array<int, array{id:int, label:string, brand:string, model:string, municipality:string, seats:string, accessibility_label:string, status:string, status_label:string, status_color:string, tracking_uuid:?string, tracking_enabled:bool, traccar_validated:bool, traccar_device_name:?string, tracking_state:string, tracking_state_label:string, tracking_state_color:string, last_located_at:string, map_url:?string, edit_url:string, missing_reason:?string}>
     */
    public function dashboardTrackingTaxis(int $limit = 2): array
    {
        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_taxis')) {
            return [];
        }

        return $this->rememberMobilePortalData(
            (int)$authId,
            'dashboard-tracking-taxis',
            ['limit' => $limit],
            function () use ($authId, $limit): array {
                $upcomingAppointments = $this->upcomingAppointmentsForTaxista((int)$authId);

                $columns = ['id', 'license_plate', 'vehicle_brand', 'vehicle_model', 'municipality', 'status'];

                if (DbSchema::hasColumn('taxista_taxis', 'seats')) {
                    $columns[] = 'seats';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'is_accessible')) {
                    $columns[] = 'is_accessible';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'last_located_at')) {
                    $columns[] = 'last_located_at';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
                    $columns[] = 'tracking_uuid';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
                    $columns[] = 'tracking_mode';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled')) {
                    $columns[] = 'tracking_simulation_enabled';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'current_lat')) {
                    $columns[] = 'current_lat';
                }

                if (DbSchema::hasColumn('taxista_taxis', 'current_lng')) {
                    $columns[] = 'current_lng';
                }

                $taxis = TaxistaTaxi::query()
                    ->where('taxista_user_id', $authId)
                    ->orderByDesc('id')
                    ->limit($limit)
                    ->get($columns);

                $traccarService = app(TraccarService::class);
                $traccarAuthenticated = $traccarService->ensureAuthenticated();

                return $taxis->map(static function (TaxistaTaxi $taxi) use ($traccarService, $traccarAuthenticated, $upcomingAppointments): array {
                    $trackingUuid = filled($taxi->tracking_uuid ?? null) ? (string)$taxi->tracking_uuid : null;

                    $trackingEnabled = true;

                    if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode') && ($taxi->tracking_mode ?? null) === 'disabled') {
                        $trackingEnabled = false;
                    }

                    $traccarDevice = null;
                    $traccarValidated = false;

                    if ($trackingEnabled && $trackingUuid && $traccarAuthenticated) {
                        $traccarDevice = $traccarService->findTraccarDeviceByUniqueId($trackingUuid);
                        $traccarValidated = is_array($traccarDevice);
                    }

                    $mapUrl = TaxistaTracking::getUrl([
                        'taxi' => (int)$taxi->getKey(),
                    ], panel: 'portal');

                    $editUrl = TaxistaTaxiResource::getUrl('edit', ['record' => $taxi->getKey()], panel: 'portal');

                    $trackingState = TaxistaTaxisTable::resolveTrackingStateForRecord($taxi);

                    $missingReason = null;

                    if (!$trackingEnabled) {
                        $missingReason = 'Seguimiento desactivado';
                    } elseif (!$trackingUuid) {
                        $missingReason = 'Sin UUID de tracking';
                    } elseif (!$traccarAuthenticated) {
                        $missingReason = 'Traccar no autenticado';
                    } elseif (!$traccarValidated) {
                        $missingReason = 'UUID no validado en Traccar';
                    }

                    return [
                        'id' => (int)$taxi->getKey(),
                        'label' => (string)($taxi->license_plate ?: ('Taxi #' . $taxi->getKey())),
                        'brand' => (string)($taxi->vehicle_brand ?? 'Sin marca'),
                        'model' => (string)($taxi->vehicle_model ?? 'Sin modelo'),
                        'municipality' => (string)($taxi->municipality ?? 'Sin municipio'),
                        'seats' => filled($taxi->seats ?? null) ? (string)$taxi->seats : '-',
                        'accessibility_label' => (bool)($taxi->is_accessible ?? false) ? 'PMR' : 'Estandar',
                        'status' => (string)($taxi->status ?? 'activo'),
                        'status_label' => match ((string)($taxi->status ?? 'activo')) {
                            'activo' => 'Activo',
                            'mantenimiento' => 'Mantenimiento',
                            'baja' => 'Baja',
                            default => ucfirst((string)($taxi->status ?? '')),
                        },
                        'status_color' => match ((string)($taxi->status ?? 'activo')) {
                            'activo' => 'emerald',
                            'mantenimiento' => 'amber',
                            'baja' => 'red',
                            default => 'zinc',
                        },
                        'tracking_uuid' => $trackingUuid,
                        'tracking_enabled' => $trackingEnabled,
                        'traccar_validated' => $traccarValidated,
                        'traccar_device_name' => is_array($traccarDevice) ? (string)($traccarDevice['name'] ?? '') : null,
                        'tracking_state' => $trackingState,
                        'tracking_state_label' => match ($trackingState) {
                            'activo' => 'Conectado',
                            'inactivo' => 'Inactivo',
                            'sin-ping' => 'Sin ping',
                            default => 'Sin código',
                        },
                        'tracking_state_color' => match ($trackingState) {
                            'activo' => 'emerald',
                            'inactivo' => 'amber',
                            'sin-ping' => 'zinc',
                            default => 'gray',
                        },
                        'last_located_at' => $taxi->last_located_at?->format('d/m/Y H:i') ?? 'Sin ubicacion',
                        'next_appointments' => $upcomingAppointments,
                        'map_url' => $mapUrl,
                        'edit_url' => $editUrl,
                        'missing_reason' => $missingReason,
                    ];
                })->all();
            },
            30,
        );
    }

    public function toggleTaxiTracking(int $taxiId): void
    {
        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_taxis')) {
            return;
        }

        $taxi = TaxistaTaxi::query()
            ->whereKey($taxiId)
            ->where('taxista_user_id', $authId)
            ->first();

        if (!$taxi) {
            return;
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
            $taxi->tracking_mode = ($taxi->tracking_mode ?? 'real') === 'disabled' ? 'real' : 'disabled';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid') && blank($taxi->tracking_uuid)) {
            $taxi->tracking_uuid = (string)Str::ulid();
        }

        $taxi->save();
        $this->flushMobilePortalCache($authId);

        Notification::make()
            ->title(($taxi->tracking_mode ?? 'real') === 'disabled' ? 'Seguimiento desactivado' : 'Seguimiento activado')
            ->body(($taxi->license_plate ?: 'Taxi') . ' actualizado')
            ->success()
            ->send();
    }

    /**
     * @return array{taxis:int, citas_hoy:int, documentos:int, tickets_abiertos:int, turnos_mes:int, turnos_m:int, turnos_p:int, turnos_n:int, turnos_l:int}
     */
    public function stats(): array
    {
        $authId = auth()->id() ?? auth('web')->id();
        $isEmployeePortal = $this->isEmployeePortal();

        if (!$authId) {
            return ['taxis' => 0, 'citas_hoy' => 0, 'documentos' => 0, 'tickets_abiertos' => 0, 'turnos_mes' => 0, 'turnos_m' => 0, 'turnos_p' => 0, 'turnos_n' => 0, 'turnos_l' => 0];
        }

        return $this->rememberMobilePortalData(
            (int)$authId,
            'stats',
            ['employee' => $isEmployeePortal],
            function () use ($authId, $isEmployeePortal): array {
                $turnosMes = 0;
                $turnosM = 0;
                $turnosP = 0;
                $turnosN = 0;
                $turnosL = 0;

                if ($isEmployeePortal && DbSchema::hasTable('employee_shifts')) {
                    $monthShifts = EmployeeShift::query()
                        ->where('employee_id', $authId)
                        ->whereDate('date', '>=', now()->startOfMonth()->toDateString())
                        ->whereDate('date', '<=', now()->endOfMonth()->toDateString())
                        ->get(['shift_code']);

                    $turnosMes = $monthShifts->count();
                    $turnosM = $monthShifts->where('shift_code', EmployeeShift::SHIFT_MANANA)->count();
                    $turnosP = $monthShifts->where('shift_code', EmployeeShift::SHIFT_PARTIDO)->count();
                    $turnosN = $monthShifts->where('shift_code', EmployeeShift::SHIFT_NOCHE)->count();
                    $turnosL = $monthShifts->where('shift_code', EmployeeShift::SHIFT_LIBRE)->count();
                }

                return [
                    'taxis' => $isEmployeePortal
                        ? 0
                        : (DbSchema::hasTable('taxista_taxis')
                            ? TaxistaTaxi::query()->where('taxista_user_id', $authId)->count()
                            : 0),
                    'citas_hoy' => DbSchema::hasTable('taxista_appointments')
                        ? TaxistaAppointment::query()->where('taxista_user_id', $authId)->count()
                        : 0,
                    'documentos' => DbSchema::hasTable('taxista_documents')
                        ? TaxistaDocument::query()->where('taxista_user_id', $authId)->count()
                        : 0,
                    'tickets_abiertos' => DbSchema::hasTable('taxista_tickets')
                        ? TaxistaTicket::query()->where('user_id', $authId)->whereIn('status', ['abierto', 'en_proceso'])->count()
                        : 0,
                    'turnos_mes' => $turnosMes,
                    'turnos_m' => $turnosM,
                    'turnos_p' => $turnosP,
                    'turnos_n' => $turnosN,
                    'turnos_l' => $turnosL,
                ];
            },
            20,
        );
    }

    public function isEmployeePortal(): bool
    {
        $authUser = $this->resolvePortalUser();

        if (!$authUser instanceof User) {
            return false;
        }

        $role = strtolower(trim((string)($authUser->role ?? '')));

        return (bool)($authUser->is_employee ?? false) || $role === 'empleado';
    }

    /**
     * @return array{name:string,email:string,nif:string,phone:string,role_label:string,department:string}
     */
    public function identificationData(): array
    {
        $authUser = $this->resolvePortalUser();

        if (!$authUser instanceof User) {
            return [
                'name' => 'Sin usuario',
                'email' => '-',
                'nif' => '-',
                'phone' => '-',
                'role_label' => 'Portal',
                'department' => '-',
            ];
        }

        $departmentName = 'Sin departamento';

        if (DbSchema::hasTable('booking_departments') && DbSchema::hasColumn('users', 'booking_department_id')) {
            $authUser->loadMissing('bookingDepartment');
            $departmentName = (string)($authUser->bookingDepartment?->name ?? 'Sin departamento');
        }

        return $this->rememberMobilePortalData(
            (int)$authUser->getKey(),
            'identification',
            ['employee' => $this->isEmployeePortal()],
            function () use ($authUser, $departmentName): array {
                return [
                    'name' => (string)($authUser->name ?? 'Sin nombre'),
                    'email' => (string)($authUser->email ?? '-'),
                    'nif' => (string)($authUser->nif ?? '-'),
                    'licencia' => (string)($authUser->licencia ?? '-'),
                    'phone' => (string)($authUser->phone ?? '-'),
                    'role_label' => $this->isEmployeePortal() ? 'Empleado' : 'Taxista',
                    'department' => $departmentName,
                ];
            },
            30,
        );
    }

    /**
     * @return array{department:string,schedule_label:string,vacation_requests:int,vacation_days_requested:int,vacation_days_approved:int,shifts_month_total:int,shifts_month_m:int,shifts_month_p:int,shifts_month_n:int,next_shift_label:string}
     */
    public function employeeSummary(): array
    {
        $authUser = $this->resolvePortalUser();

        if (!$authUser instanceof User) {
            return [
                'department' => 'Sin departamento',
                'schedule_label' => '-',
                'vacation_requests' => 0,
                'vacation_days_requested' => 0,
                'vacation_days_approved' => 0,
                'shifts_month_total' => 0,
                'shifts_month_m' => 0,
                'shifts_month_p' => 0,
                'shifts_month_n' => 0,
                'next_shift_label' => 'Sin turnos próximos',
            ];
        }

        $employeeId = (int)$authUser->getKey();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $timeOffItems = EmployeeTimeOff::query()
            ->where('employee_id', $employeeId)
            ->where('type', 'vacaciones')
            ->get(['start_date', 'end_date', 'status']);

        $vacationRequests = $timeOffItems->count();
        $vacationDaysRequested = $timeOffItems->sum(function (EmployeeTimeOff $item): int {
            return Carbon::parse((string)$item->start_date)->diffInDays(Carbon::parse((string)$item->end_date)) + 1;
        });
        $vacationDaysApproved = $timeOffItems
            ->where('status', 'approved')
            ->sum(function (EmployeeTimeOff $item): int {
                return Carbon::parse((string)$item->start_date)->diffInDays(Carbon::parse((string)$item->end_date)) + 1;
            });

        $monthShifts = EmployeeShift::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', $monthStart)
            ->whereDate('date', '<=', $monthEnd)
            ->get(['shift_code']);

        $nextShift = EmployeeShift::query()
            ->with('centralTurno:id,name,start_time,end_time')
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', today()->toDateString())
            ->orderBy('date')
            ->first();

        $nextShiftLabel = 'Sin turnos próximos';

        if ($nextShift) {
            $turnoName = (string)($nextShift->centralTurno?->name ?: $nextShift->shift_code);
            $nextShiftLabel = $nextShift->date->format('d/m/Y') . ' · ' . $turnoName;
        }

        $departmentName = 'Sin departamento';

        if (DbSchema::hasTable('booking_departments') && DbSchema::hasColumn('users', 'booking_department_id')) {
            $authUser->loadMissing('bookingDepartment');
            $departmentName = (string)($authUser->bookingDepartment?->name ?? 'Sin departamento');
        }

        return $this->rememberMobilePortalData(
            $employeeId,
            'employee-summary',
            [],
            function () use ($departmentName, $monthShifts, $nextShiftLabel, $vacationDaysApproved, $vacationDaysRequested, $vacationRequests, $employeeId): array {
                return [
                    'department' => $departmentName,
                    'schedule_label' => $this->resolveEmployeeScheduleLabel($employeeId),
                    'vacation_requests' => $vacationRequests,
                    'vacation_days_requested' => (int)$vacationDaysRequested,
                    'vacation_days_approved' => (int)$vacationDaysApproved,
                    'shifts_month_total' => $monthShifts->count(),
                    'shifts_month_m' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_MANANA)->count(),
                    'shifts_month_p' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_PARTIDO)->count(),
                    'shifts_month_n' => $monthShifts->where('shift_code', EmployeeShift::SHIFT_NOCHE)->count(),
                    'next_shift_label' => $nextShiftLabel,
                ];
            },
            30,
        );
    }

    /**
     * @return array{timeoff: array<int, array{id:int, range:string, type:string, notes:?string}>, swaps: array<int, array{id:int, date:string, type_label:string, notes:?string}>}
     */
    public function pendingRequests(): array
    {
        $authUser = $this->resolvePortalUser();

        if (!$authUser instanceof User) {
            return ['timeoff' => [], 'swaps' => []];
        }

        $employeeId = (int)$authUser->getKey();

        $timeoff = DbSchema::hasTable('employee_time_off')
            ? EmployeeTimeOff::query()
                ->where('employee_id', $employeeId)
                ->where('status', EmployeeTimeOff::STATUS_PENDING)
                ->orderByDesc('start_date')
                ->limit(5)
                ->get(['id', 'start_date', 'end_date', 'type', 'notes'])
                ->map(fn(EmployeeTimeOff $item): array => [
                    'id' => (int)$item->id,
                    'range' => $item->start_date->format('d/m/Y') . ($item->start_date->ne($item->end_date) ? ' - ' . $item->end_date->format('d/m/Y') : ''),
                    'type' => match ($item->type) {
                        'vacaciones' => 'Vacaciones',
                        'personal' => 'Día personal',
                        'baja' => 'Baja médica',
                        'permiso' => 'Permiso',
                        default => ucfirst((string)$item->type),
                    },
                    'notes' => $item->notes,
                ])
                ->all()
            : [];

        $swaps = DbSchema::hasTable('shift_swap_requests')
            ? ShiftSwapRequest::query()
                ->where('requester_user_id', $employeeId)
                ->where('status', ShiftSwapRequest::STATUS_PENDING)
                ->orderByDesc('swap_date')
                ->limit(5)
                ->get(['id', 'swap_date', 'type', 'requester_notes'])
                ->map(fn(ShiftSwapRequest $item): array => [
                    'id' => (int)$item->id,
                    'date' => $item->swap_date->format('d/m/Y'),
                    'type_label' => $item->getTypeLabel(),
                    'notes' => $item->requester_notes,
                ])
                ->all()
            : [];

        return $this->rememberMobilePortalData(
            $employeeId,
            'pending-requests',
            [],
            static fn(): array => ['timeoff' => $timeoff, 'swaps' => $swaps],
            20,
        );
    }

    private function resolvePortalUser(): ?User
    {
        $guardUser = auth()->user() ?? auth('web')->user();

        if (!$guardUser) {
            return null;
        }

        $userId = (int)($guardUser->getAuthIdentifier() ?? 0);

        if ($userId <= 0) {
            return null;
        }

        return User::query()->find($userId);
    }

    private function rememberMobilePortalData(int $userId, string $segment, array $context, callable $callback, int $ttlSeconds = 20): mixed
    {
        $version = $this->mobilePortalCacheVersion($userId);
        $cacheKey = sprintf(
            'mobile-portal:%d:%d:%s:%s',
            $userId,
            $version,
            $segment,
            md5(json_encode($context, JSON_THROW_ON_ERROR)),
        );

        if (array_key_exists($cacheKey, $this->runtimeCache)) {
            return $this->runtimeCache[$cacheKey];
        }

        /** @var CacheRepository $cache */
        $cache = Cache::store();

        return $this->runtimeCache[$cacheKey] = $cache->remember(
            $cacheKey,
            now()->addSeconds($ttlSeconds),
            $callback,
        );
    }

    private function mobilePortalCacheVersion(int $userId): int
    {
        return (int)Cache::get("mobile-portal:version:{$userId}", 1);
    }

    private function flushMobilePortalCache(?int $userId = null): void
    {
        $userId ??= (int)(auth()->id() ?? auth('web')->id() ?? 0);

        if ($userId <= 0) {
            $this->runtimeCache = [];

            return;
        }

        Cache::forever(
            "mobile-portal:version:{$userId}",
            $this->mobilePortalCacheVersion($userId) + 1,
        );

        $this->runtimeCache = [];
    }

    private function resolveEmployeeScheduleLabel(int $employeeId): string
    {
        $lastShift = EmployeeShift::query()
            ->where('employee_id', $employeeId)
            ->whereDate('date', '>=', now()->subDays(14)->toDateString())
            ->latest('date')
            ->value('shift_code');

        return match ((string)$lastShift) {
            EmployeeShift::SHIFT_MANANA => 'Turno habitual: Mañana (M)',
            EmployeeShift::SHIFT_PARTIDO => 'Turno habitual: Partido (P)',
            EmployeeShift::SHIFT_NOCHE => 'Turno habitual: Noche (N)',
            default => 'Horario rotativo',
        };
    }

    public function toggleOnline(): void
    {
        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (!$user) {
            return;
        }

        $user->is_online = !(bool)($user->is_online ?? false);
        $user->save();
        $this->flushMobilePortalCache((int)$user->getKey());

        $this->isOnline = (bool)$user->is_online;
        // dd($user->is_online);
        try {
            event(new TaxistaPresenceUpdated(
                taxistaUserId: (int)$user->getKey(),
                isOnline: (bool)$user->is_online,
                updatedAtIso: now()->toISOString(),
            ));
        } catch (\Throwable $exception) {
            Log::warning('MobilePortal: exception broadcasting presence update', [
                'user_id' => (int)$user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function saveLocation(float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return;
        }

        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (!$user) {
            return;
        }

        $user->last_lat = $lat;
        $user->last_lng = $lng;
        $user->last_location_at = now();
        $user->save();
        $this->flushMobilePortalCache((int)$user->getKey());

        $this->lastLat = $lat;
        $this->lastLng = $lng;
        $this->lastLocationAt = $user->last_location_at?->format('d/m/Y H:i');

        $this->forceOnlinePresenceForLocation($user);

        try {
            event(new TaxistaLocationUpdated(
                taxistaUserId: (int)$user->getKey(),
                lat: $lat,
                lng: $lng,
                updatedAtIso: now()->toISOString(),
            ));
        } catch (\Throwable $exception) {
            Log::warning('MobilePortal: exception broadcasting location update', [
                'user_id' => (int)$user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }

        $this->syncLocationToTraccar((int)$user->getKey(), $lat, $lng);

        Notification::make()
            ->title('Ubicacion compartida')
            ->body('Se ha guardado tu ultima ubicacion para Operaciones.')
            ->success()
            ->send();
    }

    public function trackLocation(float $lat, float $lng, float $speed = 0, float $heading = 0, ?float $accuracy = null): void
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return;
        }

        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (!$user) {
            return;
        }

        $user->last_lat = $lat;
        $user->last_lng = $lng;
        $user->last_location_at = now();
        $user->save();
        $this->flushMobilePortalCache((int)$user->getKey());

        $this->lastLat = $lat;
        $this->lastLng = $lng;
        $this->lastLocationAt = $user->last_location_at?->format('d/m/Y H:i');

        $this->forceOnlinePresenceForLocation($user);

        $this->syncLocationToTraccar((int)$user->getKey(), $lat, $lng, $speed, $heading);
    }

    public function startTracking(): void
    {
        $this->trackingActive = true;
    }

    public function stopTracking(): void
    {
        $this->trackingActive = false;
    }

    private function syncLocationToTraccar(int $userId, float $lat, float $lng, float $speed = 0, float $heading = 0): void
    {
        $trackingTaxi = $this->resolveTrackingTaxiForUser($userId);

        if ($trackingTaxi) {
            $this->storeTrackingTaxiLocationSnapshot($trackingTaxi, $lat, $lng);
        }

        $deviceId = $this->resolveTraccarDeviceIdForTrackingTaxi($trackingTaxi);

        if (! $trackingTaxi || blank($trackingTaxi->tracking_uuid)) {
            return;
        }

        try {
            $result = app(TraccarService::class)->sendClientPosition(
                uniqueId: (string) $trackingTaxi->tracking_uuid,
                latitude: $lat,
                longitude: $lng,
                recordedAt: now(),
                traccarDeviceId: $deviceId,
                attributes: [
                    'source' => 'mobile_portal',
                    'auth_user_id' => $userId,
                    'speed' => $speed,
                    'heading' => $heading,
                ],
            );

            if (! $result) {
                Log::warning('MobilePortal: failed sending location to Traccar', [
                    'user_id' => $userId,
                    'device_id' => $deviceId,
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('MobilePortal: exception sending location to Traccar', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveTraccarDeviceIdForTrackingTaxi(?TaxistaTaxi $trackingTaxi): ?int
    {
        if (!$trackingTaxi || !DbSchema::hasTable('devices')) {
            return null;
        }

        $device = TaxiDevice::query()
            ->where(function ($query) use ($trackingTaxi): void {
                $query->where('taxi_id', (int)$trackingTaxi->getKey());

                if (filled($trackingTaxi->tracking_uuid ?? null)) {
                    $query->orWhere('unique_id', (string)$trackingTaxi->tracking_uuid);
                }
            })
            ->first(['traccar_id']);

        if (!$device?->traccar_id) {
            return null;
        }

        return (int)$device->traccar_id;
    }

    private function storeTrackingTaxiLocationSnapshot(TaxistaTaxi $trackingTaxi, float $lat, float $lng): void
    {
        $payload = [];

        if (DbSchema::hasColumn('taxista_taxis', 'current_lat')) {
            $payload['current_lat'] = $lat;
        }

        if (DbSchema::hasColumn('taxista_taxis', 'current_lng')) {
            $payload['current_lng'] = $lng;
        }

        if (DbSchema::hasColumn('taxista_taxis', 'last_located_at')) {
            $payload['last_located_at'] = now();
        }

        if ($payload === []) {
            return;
        }

        TaxistaTaxi::query()
            ->whereKey($trackingTaxi->getKey())
            ->update($payload);

        $this->flushMobilePortalCache((int)$trackingTaxi->taxista_user_id);
    }

    private function resolveTrackingTaxiForUser(int $userId): ?TaxistaTaxi
    {
        if (!DbSchema::hasTable('taxista_taxis')) {
            return null;
        }

        $columns = ['id', 'taxista_user_id'];

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
            $columns[] = 'tracking_uuid';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode')) {
            $columns[] = 'tracking_mode';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled')) {
            $columns[] = 'tracking_simulation_enabled';
        }

        $taxi = TaxistaTaxi::query()
            ->where('taxista_user_id', $userId)
            ->latest('id')
            ->first($columns);

        if (!$taxi) {
            return null;
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode') && ($taxi->tracking_mode ?? null) === 'disabled') {
            return null;
        }

        if (
            DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled')
            && !DbSchema::hasColumn('taxista_taxis', 'tracking_mode')
            && !(bool)($taxi->tracking_simulation_enabled ?? false)
        ) {
            return null;
        }

        if (!DbSchema::hasColumn('taxista_taxis', 'tracking_uuid') || blank($taxi->tracking_uuid)) {
            return null;
        }

        return $taxi;
    }

    private function forceOnlinePresenceForLocation(User $user): void
    {
        if (!(bool)($user->is_online ?? false)) {
            $user->forceFill(['is_online' => true])->save();
            $this->flushMobilePortalCache((int)$user->getKey());
        }

        $this->isOnline = true;

        try {
            event(new TaxistaPresenceUpdated(
                taxistaUserId: (int)$user->getKey(),
                isOnline: true,
                updatedAtIso: now()->toISOString(),
            ));
        } catch (\Throwable $exception) {
            Log::warning('MobilePortal: exception broadcasting presence update from location', [
                'user_id' => (int)$user->getKey(),
                'error' => $exception->getMessage(),
            ]);
        }
    }

    public function locationFailed(string $message): void
    {
        Notification::make()
            ->title('Ubicacion no disponible')
            ->body($message)
            ->warning()
            ->send();
    }

    /**
     * @return array<int, array{id:int, title:string, date:string, is_favorite:bool, url:string}>
     */
    public function recentDocuments(int $limit = 5): array
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        return $this->rememberMobilePortalData(
            (int)$authId,
            'recent-documents',
            ['limit' => $limit],
            static function () use ($authId, $limit): array {
                return TaxistaDocument::query()
                    ->where('taxista_user_id', $authId)
                    ->orderByDesc('uploaded_at')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get(['id', 'title', 'uploaded_at', 'created_at', 'is_favorite'])
                    ->map(fn(TaxistaDocument $doc): array => [
                        'id' => (int)$doc->getKey(),
                        'title' => (string)($doc->title ?: 'Documento'),
                        'date' => ($doc->uploaded_at ?? $doc->created_at)?->format('d/m/Y') ?? '-',
                        'is_favorite' => (bool)$doc->is_favorite,
                        'url' => TaxistaDocumentResource::getUrl('view', ['record' => $doc->getKey()], panel: 'portal'),
                    ])
                    ->all();
            },
            20,
        );
    }

    /**
     * @return array<int, array{id:int, title:string, subtitle:string, status:string, priority:string, url:string}>
     */
    public function recentTickets(int $limit = 3): array
    {
        $authId = auth()->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_tickets')) {
            return [];
        }

        return $this->rememberMobilePortalData(
            (int)$authId,
            'recent-tickets',
            ['limit' => $limit],
            static function () use ($authId, $limit): array {
                return TaxistaTicket::query()
                    ->where('user_id', $authId)
                    ->whereIn('status', ['abierto', 'en_proceso'])
                    ->orderByDesc('opened_at')
                    ->limit($limit)
                    ->get(['id', 'title', 'status', 'priority', 'opened_at'])
                    ->map(fn(TaxistaTicket $t): array => [
                        'id' => (int)$t->getKey(),
                        'title' => (string)($t->title ?: 'Ticket'),
                        'subtitle' => $t->opened_at?->format('d/m/Y') ?? '-',
                        'status' => (string)($t->status ?? 'abierto'),
                        'status_label' => match ((string)($t->status ?? 'abierto')) {
                            'abierto' => 'Abierto',
                            'en_proceso' => 'En proceso',
                            'resuelto' => 'Resuelto',
                            default => ucfirst((string)($t->status ?? '')),
                        },
                        'badge_color' => match ((string)($t->status ?? 'abierto')) {
                            'abierto' => 'red',
                            'en_proceso' => 'amber',
                            'resuelto' => 'emerald',
                            default => 'zinc',
                        },
                        'priority' => (string)($t->priority ?? 'media'),
                        'priority_class' => match ((string)($t->priority ?? 'media')) {
                            'alta' => 'text-red-300',
                            'media' => 'text-amber-300',
                            default => 'text-zinc-400',
                        },
                        'url' => TaxistaTicketResource::getUrl('view', ['record' => $t->getKey()], panel: 'portal'),
                    ])
                    ->all();
            },
            20,
        );
    }

    /**
     * @return array<int, array{id:int, plate:string, brand:string, model:string, municipality:string, seats:string, accessibility_label:string, status:string, status_label:string, badge_color:string, tracking_uuid:?string, tracking_state:string, tracking_state_label:string, tracking_badge_color:string, last_located_at:string, url:string, map_url:string}>
     */
    public function recentTaxis(int $limit = 3): array
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_taxis')) {
            return [];
        }

        $upcomingAppointments = $this->upcomingAppointmentsForTaxista((int)$authId);

        $columns = ['id', 'license_plate', 'vehicle_brand', 'vehicle_model', 'vehicle_type', 'status'];

        if (DbSchema::hasColumn('taxista_taxis', 'municipality')) {
            $columns[] = 'municipality';
        }
        if (DbSchema::hasColumn('taxista_taxis', 'vehicle_type')) {
            $columns[] = 'vehicle_type';
        }
        if (DbSchema::hasColumn('taxista_taxis', 'seats')) {
            $columns[] = 'seats';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'is_accessible')) {
            $columns[] = 'is_accessible';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_uuid')) {
            $columns[] = 'tracking_uuid';
        }

        if (DbSchema::hasColumn('taxista_taxis', 'last_located_at')) {
            $columns[] = 'last_located_at';
        }

        return $this->rememberMobilePortalData(
            (int)$authId,
            'recent-taxis',
            ['limit' => $limit],
            function () use ($authId, $columns, $limit, $upcomingAppointments): array {
                return TaxistaTaxi::query()
                    ->where('taxista_user_id', $authId)
                    ->orderByDesc('updated_at')
                    ->limit($limit)
                    ->get($columns)
                    ->map(function (TaxistaTaxi $t) use ($upcomingAppointments): array {
                        $trackingState = TaxistaTaxisTable::resolveTrackingStateForRecord($t);

                        return [
                            'id' => (int)$t->getKey(),
                            'plate' => (string)($t->license_plate ?? '-'),
                            'brand' => (string)($t->vehicle_brand ?? 'Sin marca'),
                            'model' => (string)($t->vehicle_model ?? 'Sin modelo'),
                            'municipality' => (string)($t->municipality ?? 'Sin municipio'),
                            'vehicle_type' => (string)($t->vehicle_type ?? '-'),
                            'seats' => filled($t->seats ?? null) ? (string)$t->seats : '-',
                            'accessibility_label' => (bool)($t->is_accessible ?? false) ? 'PMR' : 'Estandar',
                            'status' => (string)($t->status ?? 'activo'),
                            'status_label' => match ((string)($t->status ?? 'activo')) {
                                'activo' => 'Activo',
                                'inactivo' => 'Inactivo',
                                'suspendido' => 'Suspendido',
                                'mantenimiento' => 'Mantenimiento',
                                'baja' => 'Baja',
                                default => ucfirst((string)($t->status ?? '')),
                            },
                            'badge_color' => match ((string)($t->status ?? 'activo')) {
                                'activo' => 'emerald',
                                'inactivo' => 'zinc',
                                'suspendido' => 'red',
                                'mantenimiento' => 'amber',
                                'baja' => 'red',
                                default => 'zinc',
                            },
                            'tracking_uuid' => filled($t->tracking_uuid ?? null) ? (string)$t->tracking_uuid : null,
                            'tracking_state' => $trackingState,
                            'tracking_state_label' => match ($trackingState) {
                                'activo' => 'Conectado',
                                'inactivo' => 'Inactivo',
                                'sin-ping' => 'Sin ping',
                                default => 'Sin código',
                            },
                            'tracking_badge_color' => match ($trackingState) {
                                'activo' => 'emerald',
                                'inactivo' => 'amber',
                                'sin-ping' => 'zinc',
                                default => 'gray',
                            },
                            'last_located_at' => $t->last_located_at?->format('d/m/Y H:i') ?? 'Sin ubicacion',
                            'next_appointments' => $upcomingAppointments,
                            'url' => TaxistaTaxiResource::getUrl('edit', ['record' => $t->getKey()], panel: 'portal'),
                            'map_url' => TaxistaTracking::getUrl(['taxi' => (int)$t->getKey()], panel: 'portal'),
                        ];
                    })
                    ->all();
            },
            20,
        );
    }

    /**
     * @return array<int, array{date:string,time:string,title:string}>
     */
    private function upcomingAppointmentsForTaxista(int $taxistaUserId, int $limit = 2): array
    {
        if (
            $taxistaUserId < 1
            || !DbSchema::hasTable('taxista_appointments')
            || !DbSchema::hasColumn('taxista_appointments', 'starts_at')
        ) {
            return [];
        }

        $query = TaxistaAppointment::query()
            ->where('taxista_user_id', $taxistaUserId)
            ->whereDate('starts_at', '>=', today())
            ->orderBy('starts_at');

        if (DbSchema::hasColumn('taxista_appointments', 'status')) {
            $query->whereNotIn('status', ['cancelada', 'finalizada']);
        }

        return $query
            ->limit($limit)
            ->get(['id', 'starts_at', 'title', 'status'])
            ->map(static fn(TaxistaAppointment $appointment): array => [
                'id' => (int) $appointment->getKey(),
                'date' => $appointment->starts_at?->format('d/m/Y') ?? '-',
                'time' => $appointment->starts_at?->format('H:i') ?? '-',
                'status' => $appointment->status,
                'title' => (string)($appointment->title ?: 'Cita'),
                'url' => TaxistaAppointmentResource::getUrl('view', ['record' => $appointment->getKey()], panel: 'portal'),
            ])
            ->all();
    }

    public function toggleDocumentFavorite(int $documentId): void
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (!$authId || !DbSchema::hasTable('taxista_documents')) {
            return;
        }

        $doc = TaxistaDocument::query()
            ->whereKey($documentId)
            ->where('taxista_user_id', $authId)
            ->first();

        if (!$doc) {
            return;
        }

        $doc->is_favorite = !(bool)$doc->is_favorite;
        $doc->save();
        $this->flushMobilePortalCache($authId);
    }

    public function setSelectedTicket(int $ticketId): void
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        $ticket = TaxistaTicket::query()
            ->where('id', $ticketId)
            ->where('user_id', $authId)
            ->first(['id', 'title', 'status', 'priority', 'opened_at']);

        if ($ticket) {
            $this->selectedTicket = [
                'id' => (int)$ticket->getKey(),
                'title' => (string)($ticket->title ?: 'Ticket'),
                'subtitle' => $ticket->opened_at?->format('d/m/Y') ?? '-',
                'status' => (string)($ticket->status ?? 'abierto'),
                'status_label' => match ((string)($ticket->status ?? 'abierto')) {
                    'abierto' => 'Abierto',
                    'en_progreso' => 'En proceso',
                    'resuelto' => 'Resuelto',
                    default => ucfirst((string)($ticket->status ?? '')),
                },
                'priority' => (string)($ticket->priority ?? 'media'),
                'priority_class' => match ((string)($ticket->priority ?? 'media')) {
                    'alta' => 'text-red-300',
                    'media' => 'text-amber-300',
                    default => 'text-zinc-400',
                },
                'url' => TaxistaTicketResource::getUrl('view', ['record' => $ticket->getKey()], panel: 'portal'),
            ];
        }
    }

    public function toggleTimeClock(): void
    {
        // Toggle the time clock dropdown
        $this->showTimeClockDropdown = !$this->showTimeClockDropdown;
    }

    public function toggleTracking(): void
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        if ($authId <= 0) {
            return;
        }

        if (!$this->trackingActive) {
            // Start tracking
            $this->startAutoTracking();
            $this->trackingActive = true;

            \Filament\Notifications\Notification::make()
                ->title('Seguimiento GPS Activado')
                ->body('Tu ubicación se está compartiendo en tiempo real.')
                ->success()
                ->send();
        } else {
            // Stop tracking
            $this->stopAutoTracking();
            $this->trackingActive = false;

            \Filament\Notifications\Notification::make()
                ->title('Seguimiento GPS Detenido')
                ->body('Has detenido el compartimiento de ubicación.')
                ->warning()
                ->send();
        }
    }

    private function startAutoTracking(): void
    {
        // This method would be called from JavaScript
        // The actual tracking logic is already in the Alpine.js component
    }

    private function stopAutoTracking(): void
    {
        // This method would be called from JavaScript
        // The actual tracking logic is already in the Alpine.js component
    }

    /**
     * @return array{checked_in: bool, start: ?string, end: ?string, status: ?string, id: ?int}
     */
    #[Computed]
    public function todayAttendance(): array
    {
        $userId = $this->resolveUserId();

        if (!$userId) {
            return ['checked_in' => false, 'start' => null, 'end' => null, 'status' => null, 'id' => null];
        }

        $record = Attendance::query()
            ->where('usuario_id', $userId)
            ->whereDate('date', today())
            ->latest()
            ->first();

        if (!$record) {
            return ['checked_in' => false, 'start' => null, 'end' => null, 'status' => null, 'id' => null];
        }

        $checkedIn = $record->startDate !== null && $record->endDate === null;

        return [
            'checked_in' => $checkedIn,
            'start' => $record->startDate?->format('H:i'),
            'end' => $record->endDate?->format('H:i'),
            'status' => $record->status,
            'id' => $record->id,
        ];
    }

    private function resolveUserId(): ?int
    {
        return auth()->id() ?? auth('web')->id();
    }

    public function render()
    {
        return view('livewire.mobile-portal')
            ->layout('components.layouts.guest', ['title' => __('Portal Móvil')]);
    }
}
