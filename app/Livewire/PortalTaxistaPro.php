<?php

namespace App\Livewire;

use App\Events\TaxistaLocationUpdated;
use App\Events\TaxistaPresenceUpdated;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpenseForm;
use App\Filament\App\Resources\TaxistaExpenses\TaxistaExpenseResource;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\BookingDepartment;
use App\Models\EmployeeShift;
use App\Models\Taxi\Device as TaxiDevice;
use App\Models\Taxista;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaExpense;
use App\Models\TaxistaTaxi;
use App\Models\TaxistaTicket;
use App\Models\User;
use App\Services\TraccarService;
use App\Support\PortalTaxistaContext;
use App\Support\TaxistaDocumentTypes;
use DateTimeInterface;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema as DbSchema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PortalTaxistaPro extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public string $activeTab = 'dashboard';

    public string $spotlight = '';

    public bool $showSpotlight = false;

    public bool $showAnnouncements = false;

    public bool $embedded = false;

    public bool $isOnline = false;

    public bool $trackingActive = false;

    public int $unreadAnnouncements = 0;

    /** @var array<int, array{id:int, title:string, content:?string, is_read:bool, starts_at:string}> */
    public array $announcements = [];

    public string $citasSegment = 'all';

    public string $ticketsSegment = 'open';

    /**
     * @var 'home'|'folders'|'recent'|'favorites'|'all'
     */
    public string $docsView = 'home';

    public ?string $docsFolder = null;

    public ?int $selectedDocumentId = null;

    /** @var 'view'|'edit' */
    public string $documentMode = 'view';

    /** @var array<string, mixed> */
    public array $documentFormData = [
        'title' => null,
        'file_path' => null,
        'document_type' => null,
        'is_favorite' => false,
        'meta' => [
            'reference' => null,
        ],
        'notas' => null,
    ];

    /**
     * @var 'all'|'favorites'|'recent'
     */
    public string $docsSegment = 'all';

    /**
     * @var 'recent'|'name'|'reference'
     */
    public string $docsOrder = 'recent';

    public bool $citasFilterUpcoming = true;

    public bool $citasFilterPendiente = true;

    public bool $citasFilterConfirmada = true;

    public bool $citasFilterAll = false;

    public bool $ticketsFilterOpen = true;

    public bool $ticketsFilterInProgress = true;

    public bool $ticketsFilterAll = false;

    public int $portalRefreshNonce = 0;

    private ?Taxista $taxistaRecord = null;

    /** @var array<string, mixed> */
    private array $runtimeCache = [];

    public function createCitaAction(): Action
    {
        return CreateAction::make('createCita')
            ->label('Nueva Cita')
            ->authorize(fn (): bool => PortalTaxistaContext::taxistaUserId() !== null)
            ->model(TaxistaAppointment::class)
            ->form(fn (Schema $schema): Schema => TaxistaAppointmentForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->mutateFormDataUsing(function (array $data): array {
                $data['created_by_user_id'] = $data['created_by_user_id'] ?? PortalTaxistaContext::taxistaUserId();
                $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();

                return $data;
            })
            ->after(function (): void {
                $this->flushPortalCache();
                $this->activeTab = 'citas';
                $this->citasFilterUpcoming = true;
                $this->citasFilterPendiente = true;
                $this->citasFilterConfirmada = true;
                $this->citasFilterAll = false;
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nueva Cita');
    }

    public function createDocumentoAction(): Action
    {
        return CreateAction::make('createDocumento')
            ->label('')
            ->authorize(fn (): bool => PortalTaxistaContext::taxistaUserId() !== null)
            ->model(TaxistaDocument::class)
            ->form(fn (Schema $schema): Schema => TaxistaDocumentForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->slideOver()
            ->modalHeading('Nuevo documento')
            ->mutateFormDataUsing(function (array $data): array {
                $data['uploaded_by_user_id'] = $data['uploaded_by_user_id'] ?? PortalTaxistaContext::taxistaUserId();
                $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();
                $data['status'] = filled($data['status'] ?? null) ? $data['status'] : 'activo';

                if (blank($data['title'] ?? null) && filled($data['file_path'] ?? null)) {
                    $fileName = pathinfo((string) $data['file_path'], PATHINFO_FILENAME);
                    $data['title'] = str_replace(['-', '_'], ' ', $fileName);
                }

                return $data;
            })
            ->after(function (): void {
                $this->flushPortalCache();
                $this->openDocsHome();
                $this->dispatch('refreshPortal');
            });
    }

    public function createTicketAction(): Action
    {
        return CreateAction::make('createTicket')
            ->label('Nuevo Ticket')
            ->model(TaxistaTicket::class)
            ->form(fn (Schema $schema): Schema => TaxistaTicketForm::configure($schema))
            ->createAnother(false)
            ->modalSubmitActionLabel('Crear')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->mutateFormDataUsing(function (array $data): array {
                $data['created_by_user_id'] = $data['created_by_user_id'] ?? PortalTaxistaContext::taxistaUserId();
                $data['user_id'] = PortalTaxistaContext::taxistaUserId();
                $data['status'] = filled($data['status'] ?? null) ? $data['status'] : 'abierto';

                return $data;
            })
            ->after(function (): void {
                $this->flushPortalCache();
                $this->activeTab = 'tickets';
                $this->ticketsFilterOpen = true;
                $this->ticketsFilterInProgress = true;
                $this->ticketsFilterAll = false;
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nuevo Ticket');
    }

    public function shouldShowCreateCitaSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createCita', 'starts_at'));
    }

    public function shouldShowEditCitaSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('editCita', 'starts_at'));
    }

    public function shouldShowCreateDocumentoSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createDocumento', 'file_path'));
    }

    public function shouldShowCreateTicketSubmit(): bool
    {
        return filled($this->getMountedActionDataValue('createTicket', 'priority'));
    }

    public function createIncidenciaAction(): Action
    {
        return $this->makeQuickTicketAction(
            'createIncidencia',
            'INCIDENCIA',
            'errores',
            'alta',
            now()->endOfDay(),
        );
    }

    public function editCitaAction(): Action
    {
        return Action::make('editCita')
            ->label('Editar Cita')
            ->authorize(function (array $arguments): bool {
                $record = $this->resolvePortalAppointmentRecord((int) ($arguments['record'] ?? 0));

                return $record !== null;
            })
            ->schema(fn (Schema $schema): Schema => TaxistaAppointmentForm::configure($schema))
            ->fillForm(function (array $arguments): array {
                $record = $this->resolvePortalAppointmentRecord((int) ($arguments['record'] ?? 0));

                if (! $record) {
                    return [];
                }

                return [
                    'created_by_user_id' => $record->created_by_user_id,
                    'taxista_user_id' => $record->taxista_user_id,
                    'booking_department_id' => $record->booking_department_id,
                    'booking_department_label' => $record->department?->name,
                    'tipo_cita_id' => $record->tipo_cita_id,
                    'tipo_cita_label' => $record->tipo?->nombre,
                    'appointment_date' => $record->starts_at?->toDateString(),
                    'starts_at' => $record->starts_at?->format('Y-m-d H:i:s'),
                    'ends_at' => $record->ends_at?->format('Y-m-d H:i:s'),
                    'notes' => $record->notes,
                    'status' => $record->status,
                    'editing_record_id' => $record->getKey(),
                ];
            })
            ->action(function (array $data, array $arguments): void {
                $record = $this->resolvePortalAppointmentRecord((int) ($arguments['record'] ?? 0));

                if (! $record) {
                    return;
                }

                $record->update([
                    'taxista_user_id' => PortalTaxistaContext::taxistaUserId(),
                    'booking_department_id' => $data['booking_department_id'] ?? $record->booking_department_id,
                    'tipo_cita_id' => $data['tipo_cita_id'] ?? $record->tipo_cita_id,
                    'starts_at' => $data['starts_at'] ?? $record->starts_at,
                    'ends_at' => $data['ends_at'] ?? $record->ends_at,
                    'notes' => $data['notes'] ?? null,
                    'status' => $data['status'] ?? $record->status,
                ]);

                $this->flushPortalCache();
                $this->activeTab = 'citas';
                $this->dispatch('refreshPortal');
            })
            ->modalSubmitAction(fn (Action $action): Action => $action->hidden(fn (): bool => ! $this->shouldShowEditCitaSubmit()))
            ->modalSubmitActionLabel('Guardar cambios')
            ->modalCancelActionLabel('Cancelar')
            ->closeModalByClickingAway(true)
            ->closeModalByEscaping(true)
            ->modalHeading('Editar Cita');
    }

    public function createSugerenciaAction(): Action
    {
        return $this->makeQuickTicketAction(
            'createSugerencia',
            'SUGERENCIA',
            'sugerencia',
            'baja',
            null,
        );
    }

    public function createGastoAction(): Action
    {
        return CreateAction::make('createGasto')
            ->label('Nuevo Gasto')
            ->model(TaxistaExpense::class)
            ->form(fn (Schema $schema): Schema => TaxistaExpenseForm::configure($schema))
            ->mutateFormDataUsing(function (array $data): array {
                $data['created_by_user_id'] = $data['created_by_user_id'] ?? PortalTaxistaContext::taxistaUserId();
                $data['taxista_user_id'] = PortalTaxistaContext::taxistaUserId();

                return $data;
            })
            ->after(function (): void {
                $this->flushPortalCache();
                $this->dispatch('refreshPortal');
            })
            ->modalHeading('Nuevo Gasto');
    }

    public function mount(): void
    {
        $this->taxistaRecord = $this->resolveTaxista();

        $authUser = auth('taxista')->user() ?? auth('web')->user();
        $this->isOnline = (bool) ($authUser?->is_online ?? false);

        $tab = (string) request()->query('tab', '');

        if (in_array($tab, ['dashboard', 'documentos', 'citas', 'tickets', 'gastos', 'cobros', 'taxis', 'chats', 'anuncios'], true)) {
            $this->activeTab = $tab === 'anuncios' ? 'dashboard' : $tab;
        }
        $this->loadAnnouncements();

        if ($tab === 'anuncios') {
            $this->openAnnouncements();
        }

        // Auto-open the "Avisos" tab once when there are new unread announcements.
        // We store the last seen unread count in session to avoid forcing the tab on every refresh.
        $this->autoOpenAnnouncementsTab();

        $folder = trim((string) request()->query('folder', ''));

        if ($folder !== '') {
            $this->docsFolder = strtoupper($folder);
            $this->docsView = 'folders';
        }

        $docsView = trim((string) request()->query('docs', ''));

        if (in_array($docsView, ['home', 'folders', 'recent', 'favorites', 'all'], true)) {
            $this->docsView = $docsView;
        }

        if ((string) request()->query('spotlight', '') === '1') {
            $this->openSpotlight();
        }
    }

    #[On('portal-taxista-refresh')]
    public function handlePortalTaxistaRefresh(array $notification = []): void
    {
        $payload = is_array($notification['notification'] ?? null)
            ? $notification['notification']
            : $notification;

        $entity = strtolower(trim((string) ($payload['taxista_entity'] ?? '')));
        $action = strtolower(trim((string) ($payload['taxista_action'] ?? '')));

        if (! in_array($entity, ['appointment', 'document', 'ticket', 'timeoff', 'shift_swap'], true)) {
            return;
        }

        if (! in_array($action, ['', 'status_changed', 'updated', 'created', 'answered'], true)) {
            return;
        }

        $this->flushPortalCache();
        $this->portalRefreshNonce++;
    }

    public function toggleOnline(): void
    {
        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (! $authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (! $user) {
            return;
        }

        $user->is_online = ! (bool) ($user->is_online ?? false);
        $user->save();

        $this->isOnline = (bool) $user->is_online;

        event(new TaxistaPresenceUpdated(
            taxistaUserId: (int) $user->getKey(),
            isOnline: (bool) $user->is_online,
            updatedAtIso: now()->toISOString(),
        ));

        Notification::make()
            ->title($user->is_online ? 'Estas online' : 'Estas offline')
            ->success()
            ->send();
    }

    public function saveLocation(float $lat, float $lng): void
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return;
        }

        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (! $authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (! $user) {
            return;
        }

        $user->last_lat = $lat;
        $user->last_lng = $lng;
        $user->last_location_at = now();
        $user->save();

        $this->forceOnlinePresenceForLocation($user);

        event(new TaxistaLocationUpdated(
            taxistaUserId: (int) $user->getKey(),
            lat: $lat,
            lng: $lng,
            updatedAtIso: now()->toISOString(),
        ));

        $this->syncLocationToTraccar((int) $user->getKey(), $lat, $lng);

        Notification::make()
            ->title('Ubicacion compartida')
            ->body('Se ha guardado tu ultima ubicacion para Operaciones.')
            ->success()
            ->send();
    }

    private function getMountedActionDataValue(string $actionName, string $path): mixed
    {
        foreach (array_reverse($this->mountedActions) as $mountedAction) {
            if (($mountedAction['name'] ?? null) !== $actionName) {
                continue;
            }

            return data_get($mountedAction['data'] ?? [], $path);
        }

        return null;
    }

    private function resolvePortalAppointmentRecord(int $recordId): ?TaxistaAppointment
    {
        if ($recordId < 1) {
            return null;
        }

        $query = TaxistaAppointment::query()
            ->with(['department:id,name', 'tipo:id,nombre']);

        PortalTaxistaContext::scopeTaxistaRecordQuery($query, 'taxista_user_id');

        return $query->find($recordId);
    }

    public function trackLocation(float $lat, float $lng, float $speed = 0, float $heading = 0, ?float $accuracy = null): void
    {
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            return;
        }

        $authId = auth('taxista')->id() ?? auth('web')->id();

        if (! $authId) {
            return;
        }

        $user = User::query()->find($authId);

        if (! $user) {
            return;
        }

        $user->last_lat = $lat;
        $user->last_lng = $lng;
        $user->last_location_at = now();
        $user->save();

        $this->forceOnlinePresenceForLocation($user);

        $this->syncLocationToTraccar((int) $user->getKey(), $lat, $lng, $speed, $heading);
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
            $traccarService = app(TraccarService::class);
            $traccarService->sendClientPosition(
                uniqueId: (string) $trackingTaxi->tracking_uuid,
                latitude: $lat,
                longitude: $lng,
                recordedAt: now(),
                traccarDeviceId: $deviceId,
                attributes: [
                    'source' => 'portal_taxista_pro',
                    'auth_user_id' => $userId,
                    'speed' => $speed,
                    'heading' => $heading,
                ],
            );
        } catch (\Throwable $exception) {
            Log::warning('PortalTaxistaPro: exception sending location to Traccar', [
                'user_id' => $userId,
                'device_id' => $deviceId,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function resolveTraccarDeviceIdForTrackingTaxi(?TaxistaTaxi $trackingTaxi): ?int
    {
        if (! $trackingTaxi || ! DbSchema::hasTable('devices')) {
            return null;
        }

        $device = TaxiDevice::query()
            ->where(function ($query) use ($trackingTaxi): void {
                $query->where('taxi_id', (int) $trackingTaxi->getKey());

                if (filled($trackingTaxi->tracking_uuid ?? null)) {
                    $query->orWhere('unique_id', (string) $trackingTaxi->tracking_uuid);
                }
            })
            ->first(['traccar_id']);

        if (! $device?->traccar_id) {
            return null;
        }

        return (int) $device->traccar_id;
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

        $this->flushPortalCache();
    }

    private function resolveTrackingTaxiForUser(int $userId): ?TaxistaTaxi
    {
        if (! DbSchema::hasTable('taxista_taxis')) {
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

        if (! $taxi) {
            return null;
        }

        if (DbSchema::hasColumn('taxista_taxis', 'tracking_mode') && ($taxi->tracking_mode ?? null) === 'disabled') {
            return null;
        }

        if (
            DbSchema::hasColumn('taxista_taxis', 'tracking_simulation_enabled')
            && ! DbSchema::hasColumn('taxista_taxis', 'tracking_mode')
            && ! (bool) ($taxi->tracking_simulation_enabled ?? false)
        ) {
            return null;
        }

        if (! DbSchema::hasColumn('taxista_taxis', 'tracking_uuid') || blank($taxi->tracking_uuid)) {
            return null;
        }

        return $taxi;
    }

    private function forceOnlinePresenceForLocation(User $user): void
    {
        if (! (bool) ($user->is_online ?? false)) {
            $user->forceFill(['is_online' => true])->save();
        }

        $this->isOnline = true;

        event(new TaxistaPresenceUpdated(
            taxistaUserId: (int) $user->getKey(),
            isOnline: true,
            updatedAtIso: now()->toISOString(),
        ));
    }

    public function toggleDocumentoFavorito(int $documentId): void
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return;
        }

        $doc = TaxistaDocument::query()
            ->whereKey($documentId)
            ->where('taxista_user_id', (int) $taxista->getKey())
            ->first();

        if (! $doc) {
            return;
        }

        $doc->is_favorite = ! (bool) $doc->is_favorite;
        $doc->save();
        $this->flushPortalCache((int) $taxista->getKey());
    }

    public function openDocumentoEnCarpeta(string $folder, int $documentId): void
    {
        $folder = strtoupper(trim($folder));

        if ($folder === '') {
            return;
        }

        $this->openDocsFolder($folder);
        $this->openDocumento($documentId);
        $this->documentMode = 'view';
    }

    public function locationFailed(string $message): void
    {
        Notification::make()
            ->title('Ubicacion no disponible')
            ->body($message)
            ->warning()
            ->send();
    }

    public function switchTab(string $tab): void
    {
        if ($tab === 'anuncios') {
            $this->openAnnouncements();

            return;
        }

        $this->activeTab = $tab;
        $this->showSpotlight = false;

        if ($tab !== 'documentos') {
            $this->docsFolder = null;
            $this->docsView = 'home';
            $this->docsSegment = 'all';
            $this->selectedDocumentId = null;
            $this->documentMode = 'view';
        }
    }

    private function loadAnnouncements(): void
    {
        $audience = $this->resolveAnnouncementAudience();

        if (! $audience || ! DbSchema::hasTable('announcements') || ! method_exists($audience, 'unreadAnnouncements')) {
            $this->unreadAnnouncements = 0;
            $this->announcements = [];

            return;
        }

        $audienceId = (int) $audience->getKey();

        $this->unreadAnnouncements = (int) $this->rememberPortalData(
            $audienceId,
            'announcements:unread-count',
            [],
            static fn () => (int) $audience->unreadAnnouncements()->count(),
            15,
        );

        $this->announcements = $this->rememberPortalData(
            $audienceId,
            'announcements:list',
            [],
            static function () use ($audience): array {
                return $audience->unreadAnnouncements()
                    ->limit(20)
                    ->get()
                    ->map(fn ($announcement) => [
                        'id' => (int) $announcement->getKey(),
                        'title' => (string) $announcement->title,
                        'content' => $announcement->content,
                        'read' => false,
                        'starts_at' => (string) optional($announcement->starts_at)->format('d/m/Y'),
                    ])
                    ->all();
            },
            15,
        );
    }

    private function autoOpenAnnouncementsTab(): void
    {
        $key = 'portal_taxista_pro.last_seen_unread_announcements';
        $lastSeen = (int) session($key, 0);

        if ($this->unreadAnnouncements <= 0) {
            session()->put($key, 0);

            return;
        }

        // If the user has not yet viewed the latest unread announcements, show the overlay.
        if ($this->activeTab === 'dashboard' && $this->unreadAnnouncements > $lastSeen) {
            $this->showAnnouncements = true;
        }

        session()->put($key, $this->unreadAnnouncements);
    }

    public function markAnnouncementAsRead(int $announcementId): void
    {
        $audience = $this->resolveAnnouncementAudience();

        if (! $audience || ! DbSchema::hasTable('announcements') || ! method_exists($audience, 'unreadAnnouncements')) {
            return;
        }

        $announcement = $audience->unreadAnnouncements()->find($announcementId);

        if (! $announcement) {
            return;
        }

        $audience->markAnnouncementAsRead($announcement);

        $this->flushPortalCache((int) $audience->getKey());
        $this->loadAnnouncements();
    }

    public function markAllAnnouncementsAsRead(): void
    {
        $audience = $this->resolveAnnouncementAudience();

        if (! $audience || ! DbSchema::hasTable('announcements') || ! method_exists($audience, 'unreadAnnouncements')) {
            return;
        }

        $audience->unreadAnnouncements()->get()->each(fn ($announcement) => $audience->markAnnouncementAsRead($announcement));

        $this->flushPortalCache((int) $audience->getKey());
        $this->loadAnnouncements();
    }

    public function openDocsHome(string $view = 'home'): void
    {
        if (! in_array($view, ['home', 'folders', 'recent', 'favorites', 'all'], true)) {
            $view = 'home';
        }

        $this->activeTab = 'documentos';
        $this->docsView = $view;
        $this->docsFolder = null;
        $this->docsSegment = 'all';
        $this->docsOrder = 'recent';
        $this->showSpotlight = false;
        $this->selectedDocumentId = null;
        $this->documentMode = 'view';
    }

    public function setDocsView(string $view): void
    {
        if (! in_array($view, ['home', 'folders', 'recent', 'favorites', 'all'], true)) {
            return;
        }

        $this->openDocsHome($view);
    }

    public function openDocsFolder(string $folder): void
    {
        $this->activeTab = 'documentos';
        $this->docsView = 'folders';
        $this->docsFolder = $folder;
        $this->docsSegment = 'all';
        $this->docsOrder = 'recent';
        $this->showSpotlight = false;
        $this->selectedDocumentId = null;
        $this->documentMode = 'view';
    }

    public function closeDocsFolder(): void
    {
        $this->docsFolder = null;
        $this->docsView = 'folders';
        $this->docsSegment = 'all';
        $this->docsOrder = 'recent';
        $this->selectedDocumentId = null;
        $this->documentMode = 'view';
    }

    public function openDocumento(int $documentId): void
    {
        $this->selectedDocumentId = $documentId;
        $this->documentMode = 'view';
        $this->documentFormData = $this->defaultDocumentFormData();
    }

    public function closeDocumento(): void
    {
        $this->selectedDocumentId = null;
        $this->documentMode = 'view';
        $this->documentFormData = $this->defaultDocumentFormData();
    }

    public function editDocumento(): void
    {
        $this->documentMode = 'edit';

        $taxista = $this->resolveTaxista();

        if (! $this->selectedDocumentId || ! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            $this->form->fill();

            return;
        }

        $doc = TaxistaDocument::query()
            ->whereKey($this->selectedDocumentId)
            ->where('taxista_user_id', (int) $taxista->getKey())
            ->first();

        if (! $doc) {
            $this->form->fill();

            return;
        }

        $this->form->fill([
            'title' => $doc->title,
            'file_path' => $doc->file_path,
            'document_type' => $doc->document_type,
            'is_favorite' => (bool) $doc->is_favorite,
            'meta' => is_array($doc->meta) ? $doc->meta : [],
        ]);
    }

    public function cancelEditDocumento(): void
    {
        $this->documentMode = 'view';
    }

    public function saveDocumento(): void
    {
        $taxista = $this->resolveTaxista();

        if (! $this->selectedDocumentId || ! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return;
        }

        $doc = TaxistaDocument::query()
            ->whereKey($this->selectedDocumentId)
            ->where('taxista_user_id', (int) $taxista->getKey())
            ->first();

        if (! $doc) {
            return;
        }

        $data = $this->form->getState();

        $doc->title = (string) ($data['title'] ?? $doc->title);
        $doc->document_type = (string) ($data['document_type'] ?? $doc->document_type);
        $doc->is_favorite = (bool) ($data['is_favorite'] ?? false);

        if (array_key_exists('file_path', $data) && is_string($data['file_path']) && $data['file_path'] !== '') {
            $doc->file_path = $data['file_path'];
        }

        $meta = is_array($doc->meta) ? $doc->meta : [];

        if (is_array($data['meta'] ?? null)) {
            $meta = array_merge($meta, $data['meta']);
        }

        $doc->meta = $meta;
        $doc->save();
        $this->flushPortalCache((int) $taxista->getKey());

        $this->documentMode = 'view';

        Notification::make()
            ->title('Documento actualizado')
            ->success()
            ->send();
    }

    /** @return array<int, \Filament\Schemas\Components\Component> */
    protected function getFormSchema(): array
    {
        return [
            TextInput::make('title')
                ->label('Titulo')
                ->required()
                ->maxLength(255),

            FileUpload::make('file_path')
                ->label('Archivo')
                ->helperText('Formatos permitidos: PDF o ZIP. Tamano maximo recomendado: 5 MB.')
                ->disk('public')
                ->directory('taxistas/documents')
                ->acceptedFileTypes([
                    'application/pdf',
                    'application/zip',
                    '.zip',
                ])
                ->maxSize(5120),

            Select::make('document_type')
                ->label('Tipo de documento')
                ->options(TaxistaDocumentTypes::options())
                ->required(),

            Toggle::make('is_favorite')
                ->label('Favorito')
                ->inline(false),

            TextInput::make('meta.reference')
                ->label('Referencia')
                ->maxLength(255),

            Textarea::make('notas')
                ->label('Notas')
                ->rows(3),
        ];
    }

    protected function getFormStatePath(): ?string
    {
        return 'documentFormData';
    }

    /**
     * @return array{
     *     title:?string,
     *     file_path:mixed,
     *     document_type:?string,
     *     is_favorite:bool,
     *     meta:array{reference:?string},
     *     notas:?string
     * }
     */
    protected function defaultDocumentFormData(): array
    {
        return [
            'title' => null,
            'file_path' => null,
            'document_type' => null,
            'is_favorite' => false,
            'meta' => [
                'reference' => null,
            ],
            'notas' => null,
        ];
    }

    public function setDocsSegment(string $segment): void
    {
        if (! in_array($segment, ['all', 'favorites', 'recent'], true)) {
            return;
        }

        $this->docsSegment = $segment;
    }

    public function setDocsOrder(string $order): void
    {
        if (! in_array($order, ['recent', 'name', 'reference'], true)) {
            return;
        }

        $this->docsOrder = $order;
    }

    public function toggleCitasFilter(string $filter): void
    {
        if ($filter === 'all') {
            $this->citasFilterAll = true;
            $this->citasFilterUpcoming = true;
            $this->citasFilterPendiente = true;
            $this->citasFilterConfirmada = true;

            return;
        }

        if ($filter === 'upcoming') {
            $this->citasFilterUpcoming = ! $this->citasFilterUpcoming;
        }

        if ($filter === 'pendiente') {
            $this->citasFilterPendiente = ! $this->citasFilterPendiente;
        }

        if ($filter === 'confirmada') {
            $this->citasFilterConfirmada = ! $this->citasFilterConfirmada;
        }

        $this->citasFilterAll = false;
    }

    public function setCitasSegment(string $segment): void
    {
        if (! in_array($segment, ['all', 'pendiente', 'confirmada', 'cancelada'], true)) {
            return;
        }

        $this->citasSegment = $segment;
    }

    public function markCitaFinalizada(int $appointmentId): void
    {
        $appointment = TaxistaAppointment::query()
            ->whereKey($appointmentId)
            ->where('taxista_user_id', PortalTaxistaContext::taxistaUserId())
            ->first();

        if (! $appointment) {
            Notification::make()
                ->title('Cita no encontrada')
                ->danger()
                ->send();

            return;
        }

        $appointment->update([
            'status' => 'finalizada',
        ]);
        $this->flushPortalCache((int) $appointment->taxista_user_id);

        Notification::make()
            ->title('Cita marcada como finalizada')
            ->success()
            ->send();
    }

    public function markCitaCancelada(int $appointmentId, ?string $motivo = null): void
    {
        $appointment = TaxistaAppointment::query()
            ->whereKey($appointmentId)
            ->where('taxista_user_id', PortalTaxistaContext::taxistaUserId())
            ->first();

        if (! $appointment) {
            Notification::make()
                ->title('Cita no encontrada')
                ->danger()
                ->send();

            return;
        }

        $motivo = trim((string) $motivo);

        if ($motivo === '') {
            Notification::make()
                ->title('Indica un motivo de cancelación')
                ->warning()
                ->send();

            return;
        }

        $motivoCancelacion = 'Motivo de cancelación: '.$motivo;
        $notes = trim((string) $appointment->notes);

        $appointment->update([
            'status' => 'cancelada',
            'notes' => $notes === '' ? $motivoCancelacion : ($notes.PHP_EOL.$motivoCancelacion),
        ]);
        $this->flushPortalCache((int) $appointment->taxista_user_id);

        Notification::make()
            ->title('Cita cancelada')
            ->success()
            ->send();
    }

    public function setTicketsSegment(string $segment): void
    {
        if (! in_array($segment, ['open', 'abierto', 'en_proceso', 'resuelto', 'cerrado', 'all'], true)) {
            return;
        }

        $this->ticketsSegment = $segment;
    }

    public function toggleTicketsFilter(string $filter): void
    {
        if ($filter === 'all') {
            $this->ticketsFilterAll = true;
            $this->ticketsFilterOpen = true;
            $this->ticketsFilterInProgress = true;

            return;
        }

        if ($filter === 'open') {
            $this->ticketsFilterOpen = ! $this->ticketsFilterOpen;
        }

        if ($filter === 'in_progress') {
            $this->ticketsFilterInProgress = ! $this->ticketsFilterInProgress;
        }

        $this->ticketsFilterAll = false;
    }

    public function runQuickAction(string $actionName): void
    {
        $allowed = [
            'createCita',
            'createDocumento',
            'createTicket',
            'createIncidencia',
            'createSugerencia',
            'createGasto',
        ];

        if (! in_array($actionName, $allowed, true)) {
            return;
        }

        $this->mountAction($actionName);
        $this->closeSpotlight();
    }

    /**
     * @return array{total:int, folders:int, favorites:int, types: array<int, array{type:string, count:int}>}
     */
    public function documentosStats(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return ['total' => 0, 'folders' => 0, 'favorites' => 0, 'types' => []];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'documentos-stats',
            [],
            function () use ($taxistaId): array {
                $rows = TaxistaDocument::query()
                    ->where('taxista_user_id', $taxistaId)
                    ->where(function (Builder $query): void {
                        $query->whereNull('status')->orWhere('status', '!=', 'archivado');
                    })
                    ->selectRaw("COALESCE(NULLIF(document_type, ''), 'OTROS') as type")
                    ->selectRaw('COUNT(*) as aggregate')
                    ->selectRaw('SUM(CASE WHEN is_favorite = 1 THEN 1 ELSE 0 END) as favorites_aggregate')
                    ->groupBy('type')
                    ->orderByDesc('aggregate')
                    ->get();

                $types = $rows
                    ->map(fn ($row): array => [
                        'type' => (string) $row->type,
                        'count' => (int) $row->aggregate,
                    ])
                    ->values()
                    ->all();

                return [
                    'total' => (int) $rows->sum(fn ($row): int => (int) $row->aggregate),
                    'folders' => $rows->count(),
                    'favorites' => (int) $rows->sum(fn ($row): int => (int) $row->favorites_aggregate),
                    'types' => $types,
                ];
            },
        );
    }

    /**
     * @return array<int, array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, estado:string, url:string}>
     */
    public function documentosFavoritos(int $limit = 6): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'documentos-favoritos',
            [$limit],
            function () use ($taxistaId, $limit): array {
                return $this->baseDocumentsQuery($taxistaId)
                    ->where('is_favorite', true)
                    ->orderByDesc('uploaded_at')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(fn (TaxistaDocument $d): array => [
                        'id' => (int) $d->getKey(),
                        'nombre' => $d->title,
                        'tipo' => strtoupper((string) ($d->document_type ?? 'OTROS')),
                        'fecha' => $d->uploaded_at?->format('d/m/Y') ?? $d->created_at?->format('d/m/Y') ?? '—',
                        'favorito' => (bool) $d->is_favorite,
                        'estado' => $d->status ?? 'activo',
                        'url' => $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $d->getKey()),
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * @return array<int, array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, referencia:?string, notas:?string, archivo:?string, url_view:string, url_edit:string, file_url:?string}>
     */
    public function documentosCarpeta(string $folder, string $segment = 'all', int $limit = 50): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        if (! in_array($segment, ['all', 'favorites', 'recent'], true)) {
            $segment = 'all';
        }

        $taxistaId = (int) $taxista->getKey();
        $folder = strtoupper(trim($folder));

        $query = $this->baseDocumentsQuery($taxistaId)
            ->whereRaw("COALESCE(NULLIF(document_type, ''), 'OTROS') = ?", [$folder]);

        if ($segment === 'favorites') {
            $query->where('is_favorite', true);
        }

        if ($segment === 'recent') {
            $query->orderByDesc('uploaded_at')->orderByDesc('created_at');
        } else {
            $query->orderByDesc('is_favorite')->orderByDesc('uploaded_at')->orderByDesc('created_at');
        }

        return $this->rememberPortalData(
            $taxistaId,
            'documentos-carpeta',
            [$folder, $segment, $limit, $this->docsOrder],
            function () use ($query, $limit): array {
                $query = clone $query;

                if ($this->docsOrder === 'name') {
                    $query->orderBy('title');
                }

                if ($this->docsOrder === 'reference') {
                    $query->orderBy('meta->reference')->orderBy('title');
                }

                return $query
                    ->limit($limit)
                    ->get()
                    ->map(fn (TaxistaDocument $d): array => $this->mapDocumentoForUi($d))
                    ->toArray();
            },
        );
    }

    /**
     * @return array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, referencia:?string, notas:?string, archivo:?string, url_view:string, url_edit:string, file_url:?string}
     */
    private function mapDocumentoForUi(TaxistaDocument $doc): array
    {
        $fileUrl = null;

        if (is_string($doc->file_path) && $doc->file_path !== '') {
            $fileUrl = Storage::url($doc->file_path);
        }

        $meta = is_array($doc->meta) ? $doc->meta : [];

        $archivo = null;

        if (is_string($doc->file_path) && $doc->file_path !== '') {
            $archivo = basename($doc->file_path);
        }

        $viewUrl = $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $doc->getKey());
        $editUrl = $this->safeResourceUrl(TaxistaDocumentResource::class, 'edit', (int) $doc->getKey(), $viewUrl);

        return [
            'id' => (int) $doc->getKey(),
            'nombre' => $doc->title,
            'tipo' => strtoupper((string) ($doc->document_type ?? 'OTROS')),
            'fecha' => $doc->uploaded_at?->format('d/m/Y H:i') ?? $doc->created_at?->format('d/m/Y H:i') ?? '—',
            'favorito' => (bool) $doc->is_favorite,
            'estado' => (string) ($doc->status ?? 'activo'),
            'referencia' => is_string($meta['reference'] ?? null) ? $meta['reference'] : null,
            'notas' => is_string($meta['notas'] ?? null) ? $meta['notas'] : null,
            'archivo' => $archivo,
            'url_view' => $viewUrl,
            'url_edit' => $editUrl,
            'file_url' => $fileUrl,
        ];
    }

    /**
     * @return array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, referencia:?string, notas:?string, archivo:?string, url_view:string, url_edit:string, file_url:?string}|null
     */
    private function documentoSeleccionado(?int $documentId): ?array
    {
        if (! $documentId) {
            return null;
        }

        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return null;
        }

        $doc = $this->baseDocumentsQuery((int) $taxista->getKey())
            ->whereKey($documentId)
            ->first();

        if (! $doc) {
            return null;
        }

        $fileUrl = null;

        if (is_string($doc->file_path) && $doc->file_path !== '') {
            $fileUrl = Storage::url($doc->file_path);
        }

        $meta = is_array($doc->meta) ? $doc->meta : [];

        $archivo = null;

        if (is_string($doc->file_path) && $doc->file_path !== '') {
            $archivo = basename($doc->file_path);
        }

        $viewUrl = $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $doc->getKey());
        $editUrl = $this->safeResourceUrl(TaxistaDocumentResource::class, 'edit', (int) $doc->getKey(), $viewUrl);

        return [
            'id' => (int) $doc->getKey(),
            'nombre' => $doc->title,
            'tipo' => strtoupper((string) ($doc->document_type ?? 'OTROS')),
            'fecha' => $doc->uploaded_at?->format('d/m/Y H:i') ?? $doc->created_at?->format('d/m/Y H:i') ?? '—',
            'favorito' => (bool) $doc->is_favorite,
            'referencia' => is_string($meta['reference'] ?? null) ? $meta['reference'] : null,
            'notas' => is_string($meta['notas'] ?? null) ? $meta['notas'] : null,
            'archivo' => $archivo,
            'url_view' => $viewUrl,
            'url_edit' => $editUrl,
            'file_url' => $fileUrl,
        ];
    }

    /**
     * @return array<int, array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, estado:string, url:string}>
     */
    public function documentosRecientes(int $limit = 10): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'documentos-recientes',
            [$limit],
            function () use ($taxistaId, $limit): array {
                return $this->baseDocumentsQuery($taxistaId)
                    ->orderByDesc('uploaded_at')
                    ->orderByDesc('created_at')
                    ->limit($limit)
                    ->get()
                    ->map(fn (TaxistaDocument $d): array => [
                        'id' => (int) $d->getKey(),
                        'nombre' => $d->title,
                        'tipo' => strtoupper((string) ($d->document_type ?? 'OTROS')),
                        'fecha' => $d->uploaded_at?->format('d/m/Y') ?? $d->created_at?->format('d/m/Y') ?? '—',
                        'favorito' => (bool) $d->is_favorite,
                        'estado' => $d->status ?? 'activo',
                        'url' => $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $d->getKey()),
                    ])
                    ->toArray();
            },
        );
    }

    /**
     * @return array{count:int, favorites:int}
     */
    public function documentosCarpetaStats(string $folder): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return ['count' => 0, 'favorites' => 0];
        }

        $taxistaId = (int) $taxista->getKey();
        $folder = strtoupper($folder);

        $base = $this->baseDocumentsQuery($taxistaId)
            ->whereRaw("COALESCE(NULLIF(document_type, ''), 'OTROS') = ?", [$folder]);

        return [
            'count' => (int) (clone $base)->count(),
            'favorites' => (int) (clone $base)->where('is_favorite', true)->count(),
        ];
    }

    /**
     * @return array<int, array{id:int, nombre:?string, tipo:string, fecha:string, favorito:bool, estado:string, referencia:?string, notas:?string, archivo:?string, url_view:string, url_edit:string, file_url:?string}>
     */
    public function documentosTodos(int $limit = 80): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        return $this->rememberPortalData(
            (int) $taxista->getKey(),
            'documentos-todos',
            [$limit, $this->docsOrder],
            function () use ($taxista, $limit): array {
                $query = $this->baseDocumentsQuery((int) $taxista->getKey());

                match ($this->docsOrder) {
                    'name' => $query->orderBy('title'),
                    'reference' => $query->orderBy('meta->reference')->orderBy('title'),
                    default => $query->orderByDesc('uploaded_at')->orderByDesc('created_at'),
                };

                return $query
                    ->limit($limit)
                    ->get()
                    ->map(fn (TaxistaDocument $doc): array => $this->mapDocumentoForUi($doc))
                    ->toArray();
            },
        );
    }

    public function openSpotlight(): void
    {
        $this->showAnnouncements = false;
        $this->showSpotlight = true;
        $this->spotlight = '';
    }

    public function closeSpotlight(): void
    {
        $this->showSpotlight = false;
        $this->spotlight = '';
    }

    public function openAnnouncements(): void
    {
        $this->showSpotlight = false;
        $this->loadAnnouncements();
        $this->showAnnouncements = true;
        session()->put('portal_taxista_pro.last_seen_unread_announcements', $this->unreadAnnouncements);
    }

    public function closeAnnouncements(): void
    {
        $this->showAnnouncements = false;
    }

    public function stats(): array
    {
        $taxista = $this->resolveTaxista();
        $taxistaId = $taxista ? (int) $taxista->getKey() : PortalTaxistaContext::taxistaUserId();

        if (! $taxistaId) {
            return ['dashboard' => 0, 'citas' => 0, 'documentos' => 0, 'tickets' => 0, 'gastos' => 0, 'cobros' => 0, 'chats' => 0, 'anuncios' => 0];
        }

        return $this->rememberPortalData($taxistaId, 'stats', [], static function () use ($taxistaId): array {
            // Get turnos data
            $turnosStats = ['turnos_m' => 0, 'turnos_p' => 0, 'turnos_n' => 0, 'turnos_l' => 0, 'turnos_mes' => 0, 'proximo_turno' => null];

            if (DbSchema::hasTable('employee_shifts')) {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                $turnos = EmployeeShift::query()
                    ->where('employee_id', $taxistaId)
                    ->whereMonth('date', $currentMonth)
                    ->whereYear('date', $currentYear)
                    ->get();

                $turnosStats['turnos_m'] = $turnos->where('shift_code', 'M')->count();
                $turnosStats['turnos_p'] = $turnos->where('shift_code', 'P')->count();
                $turnosStats['turnos_n'] = $turnos->where('shift_code', 'N')->count();
                $turnosStats['turnos_l'] = $turnos->where('shift_code', 'L')->count();
                $turnosStats['turnos_mes'] = $turnos->count();

                // Get next shift
                $nextShift = EmployeeShift::query()
                    ->where('employee_id', $taxistaId)
                    ->where('date', '>=', now()->toDateString())
                    ->orderBy('date')
                    ->first();

                if ($nextShift) {
                    $shiftLabels = [
                        'M' => 'Mañana',
                        'P' => 'Partido',
                        'N' => 'Noche',
                        'L' => 'Libre',
                    ];
                    $date = Carbon::parse($nextShift->date);
                    $turnosStats['proximo_turno'] = $date->format('d/m').' - '.($shiftLabels[$nextShift->shift_code] ?? $nextShift->shift_code);
                }
            }

            $unreadAnnouncements = 0;

            if (DbSchema::hasTable('announcements')) {
                $user = User::query()->find($taxistaId);

                if ($user && method_exists($user, 'unreadAnnouncements')) {
                    $unreadAnnouncements = (int) $user->unreadAnnouncements()->count();
                }
            }

            return [
                'dashboard' => 0,
                'taxis' => DbSchema::hasTable('taxista_taxis')
                    ? TaxistaTaxi::query()->where('taxista_user_id', $taxistaId)->count()
                    : 0,
                'citas' => DbSchema::hasTable('taxista_appointments')
                    ? TaxistaAppointment::query()->where('taxista_user_id', $taxistaId)->count()
                    : 0,
                'documentos' => DbSchema::hasTable('taxista_documents')
                    ? TaxistaDocument::query()->where('taxista_user_id', $taxistaId)->count()
                    : 0,
                'tickets' => DbSchema::hasTable('taxista_tickets')
                    ? TaxistaTicket::query()->where('user_id', $taxistaId)->whereIn('status', ['abierto', 'en_proceso'])->count()
                    : 0,
                'gastos' => DbSchema::hasTable('taxista_expenses')
                    ? TaxistaExpense::query()->where('taxista_user_id', $taxistaId)->count()
                    : 0,
                'cobros' => DbSchema::hasTable('taxista_expenses')
                    ? TaxistaExpense::query()->where('taxista_user_id', $taxistaId)->count()
                    : 0,
                'chats' => 0,
                'anuncios' => $unreadAnnouncements,
            ] + $turnosStats;
        }, 60);
    }

    public function citas(): array
    {
        $taxistaId = PortalTaxistaContext::taxistaUserId();

        if (! $taxistaId || ! DbSchema::hasTable('taxista_appointments')) {
            return [];
        }

        return $this->rememberPortalData(
            $taxistaId,
            'citas',
            [
                'upcoming' => $this->citasFilterUpcoming,
                'pendiente' => $this->citasFilterPendiente,
                'confirmada' => $this->citasFilterConfirmada,
                'all' => $this->citasFilterAll,
            ],
            function () use ($taxistaId): array {
                $query = TaxistaAppointment::query()
                    ->select([
                        'id',
                        'taxista_user_id',
                        'booking_department_id',
                        'title',
                        'notes',
                        'starts_at',
                        'status',
                    ])
                    ->with(['booking_department:id,name,color'])
                    ->where('taxista_user_id', $taxistaId);

                if (! $this->citasFilterAll) {
                    $statuses = [];

                    if ($this->citasFilterPendiente) {
                        $statuses[] = 'pendiente';
                    }

                    if ($this->citasFilterConfirmada) {
                        $statuses[] = 'confirmada';
                    }

                    if ($statuses === []) {
                        return [];
                    }

                    $query->whereIn('status', $statuses);
                }

                if ($this->citasFilterUpcoming) {
                    $query
                        ->where('starts_at', '>=', now()->startOfDay())
                        ->orderBy('starts_at');
                } else {
                    $today = now()->startOfDay()->toDateTimeString();

                    $query
                        ->orderByRaw('CASE WHEN starts_at >= ? THEN 0 ELSE 1 END', [$today])
                        ->orderByRaw('CASE WHEN starts_at >= ? THEN starts_at ELSE NULL END ASC', [$today])
                        ->orderByRaw('CASE WHEN starts_at < ? THEN starts_at ELSE NULL END DESC', [$today]);
                }

                return $query
                    ->limit(20)
                    ->get()
                    ->map(function (TaxistaAppointment $a): array {
                        $appointmentUrl = $this->safeResourceUrl(TaxistaAppointmentResource::class, 'edit', (int) $a->id);

                        return [
                            'id' => $a->id,
                            'titulo' => $a->title,
                            'fecha' => $a->starts_at?->format('d/m') ?? '—',
                            'mes' => $a->starts_at?->format('M') ?? '',
                            'hora' => $a->starts_at?->format('H:i') ?? '—',
                            'lugar' => $a->notes ?? '—',
                            'estado' => $a->status ?? 'pendiente',
                            'departamento' => $a->booking_department?->name ?? '—',
                            'departamento_color' => $a->booking_department?->getAttribute('color'),
                            'url' => $appointmentUrl,
                        ];
                    })
                    ->toArray();
            },
        );
    }

    public function documentos(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_documents')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'documentos-dashboard',
            [],
            function () use ($taxistaId): array {
                return $this->baseDocumentsQuery($taxistaId)
                    ->orderByDesc('is_favorite')
                    ->orderByDesc('uploaded_at')
                    ->orderByDesc('created_at')
                    ->limit(20)
                    ->get()
                    ->map(fn (TaxistaDocument $d): array => [
                        'id' => $d->id,
                        'nombre' => $d->title,
                        'tipo' => $d->document_type ?? 'otros',
                        'fecha' => $d->uploaded_at?->format('d/m/Y') ?? $d->created_at?->format('d/m/Y') ?? '—',
                        'favorito' => (bool) $d->is_favorite,
                        'estado' => $d->status ?? 'activo',
                    ])
                    ->toArray();
            },
        );
    }

    public function tickets(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_tickets')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        $query = TaxistaTicket::query()
            ->select([
                'id',
                'user_id',
                'booking_department_id',
                'ticket_type',
                'title',
                'description',
                'attachments',
                'attachment_file_names',
                'priority',
                'status',
                'opened_at',
                'due_at',
                'created_at',
            ])
            ->with(['department:id,name'])
            ->where('user_id', $taxistaId);

        if (! $this->ticketsFilterAll) {
            $statuses = [];

            if ($this->ticketsFilterOpen) {
                $statuses[] = 'abierto';
            }

            if ($this->ticketsFilterInProgress) {
                $statuses[] = 'en_proceso';
            }

            if (count($statuses) === 0) {
                return [];
            }

            $query->whereIn('status', $statuses);
        }

        return $this->rememberPortalData(
            $taxistaId,
            'tickets',
            [$this->ticketsSegment, $this->ticketsFilterOpen, $this->ticketsFilterInProgress, $this->ticketsFilterAll],
            function () use ($query): array {
                return $query
                    ->orderByDesc('opened_at')
                    ->limit(20)
                    ->get()
                    ->map(function (TaxistaTicket $t): array {
                        $openedAt = $t->opened_at?->format('d/m/Y H:i') ?? $t->created_at?->format('d/m/Y H:i') ?? '—';
                        $dueAt = $t->due_at?->format('d/m/Y H:i');
                        $status = (string) ($t->status ?? 'abierto');
                        $priority = (string) ($t->priority ?? 'media');

                        $attachments = $this->mapTicketAttachments($t);
                        $primaryAttachmentUrl = $attachments[0]['url'] ?? null;

                        return [
                            'id' => $t->id,
                            'title' => $t->title ?: 'Ticket',
                            'subtitle' => $openedAt,
                            'description' => $t->description ?? '',
                            'status' => $status,
                            'status_label' => match ($status) {
                                'abierto' => 'Abierto',
                                'en_proceso' => 'En proceso',
                                'resuelto' => 'Resuelto',
                                default => ucfirst($status),
                            },
                            'badge_color' => match ($status) {
                                'abierto' => 'red',
                                'en_proceso' => 'amber',
                                'resuelto' => 'emerald',
                                default => 'zinc',
                            },
                            'priority' => $priority,
                            'priority_label' => match ($priority) {
                                'alta' => 'Alta',
                                'media' => 'Media',
                                'baja' => 'Baja',
                                default => ucfirst($priority),
                            },
                            'priority_class' => match ($priority) {
                                'alta' => 'text-red-300',
                                'media' => 'text-amber-300',
                                default => 'text-zinc-400',
                            },
                            'opened_at' => $openedAt,
                            'due_at' => $dueAt,
                            'department' => $t->department?->name ?? 'General',
                            'attachments' => $attachments,
                            'attachment_url' => $primaryAttachmentUrl,
                            'url' => $this->safeResourceUrl(TaxistaTicketResource::class, 'view', (int) $t->id),
                            'edit_url' => $this->safeResourceUrl(TaxistaTicketResource::class, 'edit', (int) $t->id),
                        ];
                    })
                    ->toArray();
            },
        );
    }

    public function gastos(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_expenses')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'gastos',
            [],
            function () use ($taxistaId): array {
                return TaxistaExpense::query()
                    ->select([
                        'id',
                        'taxista_user_id',
                        'taxista_expense_category_id',
                        'taxista_expense_subcategory_id',
                        'title',
                        'amount',
                        'expense_date',
                        'status',
                    ])
                    ->with([
                        'category:id,name',
                        'subcategory:id,name',
                    ])
                    ->where('taxista_user_id', $taxistaId)
                    ->orderByDesc('expense_date')
                    ->limit(20)
                    ->get()
                    ->map(fn (TaxistaExpense $e): array => [
                        'id' => $e->id,
                        'concepto' => $e->title,
                        'importe' => (float) $e->amount,
                        'fecha' => $e->expense_date?->format('d/m/Y') ?? '—',
                        'categoria' => $e->category?->name ?? $e->subcategory?->name ?? 'General',
                        'estado' => $e->status?->value ?? 'pendiente',
                    ])
                    ->toArray();
            },
        );
    }

    public function cobros(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista || ! DbSchema::hasTable('taxista_expenses')) {
            return [];
        }

        $taxistaId = (int) $taxista->getKey();

        return $this->rememberPortalData(
            $taxistaId,
            'cobros',
            [],
            function () use ($taxistaId): array {
                return TaxistaExpense::query()
                    ->select([
                        'id',
                        'taxista_user_id',
                        'title',
                        'amount',
                        'final_amount',
                        'due_date',
                        'status',
                    ])
                    ->where('taxista_user_id', $taxistaId)
                    ->orderByDesc('due_date')
                    ->limit(20)
                    ->get()
                    ->map(fn (TaxistaExpense $e): array => [
                        'id' => $e->id,
                        'concepto' => $e->title,
                        'importe' => (float) ($e->final_amount ?: $e->amount),
                        'estado' => $e->status?->value ?? 'pendiente',
                        'fecha_venc' => $e->due_date?->format('d/m/Y') ?? '—',
                    ])
                    ->toArray();
            },
        );
    }

    public function spotlightResults(): array
    {
        if (strlen($this->spotlight) < 2) {
            return [];
        }

        ['type' => $typeFilter, 'raw' => $rawNeedle, 'compact' => $compactNeedle] = $this->parseSpotlightQuery($this->spotlight);
        $taxista = $this->resolveTaxista();
        $taxistaId = $taxista ? (int) $taxista->getKey() : null;
        $matchesTaxistaIdentity = $this->matchesSpotlightNeedle($this->currentSpotlightIdentityAliases(), $rawNeedle, $compactNeedle);
        $results = [];

        if (($typeFilter === null || $typeFilter === 'cita') && $taxistaId && DbSchema::hasTable('taxista_appointments')) {
            TaxistaAppointment::query()
                ->select(['id', 'taxista_user_id', 'title', 'starts_at', 'status', 'booking_department_id', 'tipo_cita_id'])
                ->with(['department:id,name', 'tipo:id,nombre'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('starts_at')
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaAppointment $appointment): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $appointment->title,
                        $appointment->status,
                        $appointment->department?->name,
                        $appointment->tipo?->nombre,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(3)
                ->each(function (TaxistaAppointment $a) use (&$results): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Cita' => $a->title,
                            'Estado' => $a->status,
                            'Departamento' => $a->department?->name,
                            'Tipo' => $a->tipo?->nombre,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'cita',
                        'id' => $a->id,
                        'label' => $a->title,
                        'sub' => collect([
                            $matchContext,
                            $a->starts_at?->format('d/m/Y H:i'),
                        ])->filter()->implode(' · '),
                        'tab' => 'citas',
                        'url' => $this->safeResourceUrl(TaxistaAppointmentResource::class, 'view', (int) $a->id),
                    ];
                });
        }

        if ($taxistaId && DbSchema::hasTable('taxista_expenses')) {
            TaxistaExpense::query()
                ->select(['id', 'taxista_user_id', 'title', 'status'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('id')
                ->limit(30)
                ->get()
                ->filter(fn (TaxistaExpense $expense): bool => $this->matchesSpotlightNeedle(
                    [
                        $expense->title,
                        (string) ($expense->status?->value ?? 'pendiente'),
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(3)
                ->each(function (TaxistaExpense $expense) use (&$results, $rawNeedle, $compactNeedle): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Gasto' => $expense->title,
                            'Estado' => (string) ($expense->status?->value ?? 'pendiente'),
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'gasto',
                        'id' => $expense->id,
                        'label' => $expense->title,
                        'sub' => $matchContext,
                        'tab' => 'gastos',
                        'url' => TaxistaExpenseResource::getUrl('edit', ['record' => $expense->id], panel: 'portal'),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'documento') && $taxistaId && DbSchema::hasTable('taxista_documents')) {
            $this->baseDocumentsQuery($taxistaId)
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaDocument $document): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $document->title,
                        $document->document_type,
                        $document->status,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(3)
                ->each(function (TaxistaDocument $d) use (&$results): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Documento' => $d->title,
                            'Tipo' => $d->document_type,
                            'Estado' => $d->status,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'documento',
                        'id' => $d->id,
                        'label' => $d->title,
                        'sub' => collect([
                            $matchContext,
                        ])->filter()->implode(' · '),
                        'tab' => 'documentos',
                        'url' => $this->safeResourceUrl(TaxistaDocumentResource::class, 'view', (int) $d->id),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'ticket') && $taxistaId && DbSchema::hasTable('taxista_tickets')) {
            TaxistaTicket::query()
                ->select(['id', 'user_id', 'title', 'status', 'priority', 'booking_department_id'])
                ->with(['department:id,name'])
                ->where('user_id', $taxistaId)
                ->latest('opened_at')
                ->limit(50)
                ->get()
                ->filter(fn (TaxistaTicket $ticket): bool => $matchesTaxistaIdentity || $this->matchesSpotlightNeedle(
                    [
                        $ticket->title,
                        $ticket->status,
                        $ticket->priority,
                        $ticket->department?->name,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(3)
                ->each(function (TaxistaTicket $t) use (&$results): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Ticket' => $t->title,
                            'Estado' => $t->status,
                            'Prioridad' => $t->priority,
                            'Departamento' => $t->department?->name,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'ticket',
                        'id' => $t->id,
                        'label' => $t->title,
                        'sub' => collect([
                            $matchContext,
                            $t->status,
                        ])->filter()->implode(' · '),
                        'tab' => 'tickets',
                        'url' => $this->safeResourceUrl(TaxistaTicketResource::class, 'view', (int) $t->id),
                    ];
                });
        }

        if (($typeFilter === null || $typeFilter === 'taxi') && $taxistaId && DbSchema::hasTable('taxista_taxis')) {
            TaxistaTaxi::query()
                ->select(['id', 'taxista_user_id', 'license_plate', 'tracking_uuid', 'vehicle_brand', 'vehicle_model'])
                ->where('taxista_user_id', $taxistaId)
                ->latest('id')
                ->limit(30)
                ->get()
                ->filter(fn (TaxistaTaxi $taxi): bool => $this->matchesSpotlightNeedle(
                    [
                        $taxi->license_plate,
                        $taxi->tracking_uuid,
                        $taxi->vehicle_brand,
                        $taxi->vehicle_model,
                    ],
                    $rawNeedle,
                    $compactNeedle,
                ))
                ->take(3)
                ->each(function (TaxistaTaxi $taxi) use (&$results): void {
                    $matchContext = $this->spotlightMatchContext(
                        [
                            'Matricula' => $taxi->license_plate,
                            'UUID' => $taxi->tracking_uuid,
                            'Marca' => $taxi->vehicle_brand,
                            'Modelo' => $taxi->vehicle_model,
                        ],
                        $rawNeedle,
                        $compactNeedle,
                    );

                    $results[] = [
                        'type' => 'taxi',
                        'id' => $taxi->id,
                        'label' => $taxi->license_plate ?: ('Taxi '.$taxi->id),
                        'sub' => collect([
                            $matchContext,
                            collect([$taxi->vehicle_brand, $taxi->vehicle_model])->filter()->implode(' '),
                        ])->filter()->implode(' · '),
                        'tab' => 'dashboard',
                        'url' => $this->safeResourceUrl(TaxistaTaxiResource::class, 'edit', (int) $taxi->id),
                    ];
                });
        }

        return $results;
    }

    /**
     * @return array{type:?string,raw:string,compact:string}
     */
    private function parseSpotlightQuery(string $query): array
    {
        $raw = trim(Str::lower($query));
        $type = null;

        foreach (['cita' => 'cit', 'ticket' => 'tic', 'documento' => 'doc', 'taxi' => 'tax'] as $candidateType => $prefix) {
            if (Str::startsWith($raw, $prefix) && strlen($raw) > strlen($prefix)) {
                $type = $candidateType;
                $raw = ltrim(substr($raw, strlen($prefix)));
                break;
            }
        }

        return [
            'type' => $type,
            'raw' => $raw,
            'compact' => $this->normalizeSpotlightValue($raw),
        ];
    }

    /**
     * @param  array<int, string|null>  $haystacks
     */
    private function matchesSpotlightNeedle(array $haystacks, string $rawNeedle, string $compactNeedle): bool
    {
        foreach ($haystacks as $haystack) {
            $value = Str::lower((string) $haystack);

            if ($rawNeedle !== '' && str_contains($value, $rawNeedle)) {
                return true;
            }

            if ($compactNeedle !== '' && str_contains($this->normalizeSpotlightValue($value), $compactNeedle)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  array<string, string|null>  $candidates
     */
    private function spotlightMatchContext(array $candidates, string $rawNeedle, string $compactNeedle): ?string
    {
        foreach ($candidates as $label => $value) {
            $normalizedValue = Str::lower((string) $value);

            if ($rawNeedle !== '' && str_contains($normalizedValue, $rawNeedle)) {
                return $label.' · '.(string) $value;
            }

            if ($compactNeedle !== '' && str_contains($this->normalizeSpotlightValue($normalizedValue), $compactNeedle)) {
                return $label.' · '.(string) $value;
            }
        }

        return null;
    }

    private function normalizeSpotlightValue(?string $value): string
    {
        return Str::lower(preg_replace('/[^a-z0-9]+/', '', Str::ascii((string) $value)) ?? '');
    }

    /**
     * @return array<int, string|null>
     */
    private function currentSpotlightIdentityAliases(): array
    {
        $taxista = $this->resolveTaxista();

        if (! $taxista) {
            return [];
        }

        $taxista->loadMissing([
            'municipio:id,nombre',
            'taxis:id,taxista_user_id,license_plate',
        ]);

        return [
            $taxista->name,
            $taxista->licencia,
            $taxista->municipio?->nombre,
            ...$taxista->taxis->pluck('license_plate')->all(),
        ];
    }

    private function resolveTaxista(): ?Taxista
    {
        if ($this->taxistaRecord) {
            return $this->taxistaRecord;
        }

        if (! DbSchema::hasColumn('users', 'role')) {
            // Test / minimal schema environments.
            return null;
        }

        $user = auth('taxista')->user() ?? auth('web')->user();

        if (! $user) {
            return null;
        }

        if ($user instanceof Taxista) {
            $this->taxistaRecord = $user;

            return $this->taxistaRecord;
        }

        $this->taxistaRecord = Taxista::query()->whereKey($user->id)->first()
            ?? Taxista::withoutGlobalScopes()->whereKey($user->id)->first();

        return $this->taxistaRecord;
    }

    private function resolveAnnouncementAudience(): User|Taxista|null
    {
        $user = auth('taxista')->user() ?? auth('web')->user();

        if ($user && method_exists($user, 'unreadAnnouncements')) {
            return $user;
        }

        $taxista = $this->resolveTaxista();

        if ($taxista && method_exists($taxista, 'unreadAnnouncements')) {
            return $taxista;
        }

        return null;
    }

    private function makeQuickTicketAction(
        string $actionName,
        string $label,
        string $ticketType,
        string $priority,
        ?DateTimeInterface $dueAt,
    ): Action {
        return CreateAction::make($actionName)
            ->label($label)
            ->model(TaxistaTicket::class)
            ->createAnother(false)
            ->form(function (Schema $schema) use ($label): Schema {
                return $schema
                    ->components([
                        Hidden::make('ticket_type'),
                        Hidden::make('booking_department_id'),
                        Hidden::make('priority'),
                        Hidden::make('due_at'),
                        Hidden::make('status'),
                        Hidden::make('title'),
                        Textarea::make('description')
                            ->label('Descripcion')
                            ->rows(5)
                            ->required()
                            ->extraAttributes([
                                'x-effect' => "
                                    const footer = \$el.closest('.fi-modal-window')?.querySelector('.fi-modal-footer');
                                    if (! footer) return;
                                    const description = \$wire.mountedActions?.[0]?.data?.description ?? '';
                                    footer.style.display = description.trim().length > 2 ? 'flex' : 'none';
                                ",
                            ])
                            ->helperText("Describe {$label} y al guardar se pedirá la captura de pantalla."),
                    ]);
            })
            ->mutateFormDataUsing(function (array $data) use ($label, $ticketType, $priority, $dueAt): array {
                $taxistaId = PortalTaxistaContext::taxistaUserId();
                $departmentId = $this->resolveSupportDepartmentId()
                    ?? $this->resolveTaxista()?->booking_department_id
                    ?? auth()->user()?->booking_department_id;

                $data['created_by_user_id'] = $data['created_by_user_id'] ?? $taxistaId;
                $data['user_id'] = $taxistaId;
                $data['ticket_type'] = $ticketType;
                $data['booking_department_id'] = $departmentId;
                $data['priority'] = $priority;
                $data['due_at'] = $dueAt;
                $data['status'] = 'abierto';
                $data['title'] = $this->composeQuickTicketTitle($label);

                return $data;
            })
            ->after(function (CreateAction $action, TaxistaTicket $record) use ($label): void {
                $this->flushPortalCache();
                $this->activeTab = 'tickets';
                $this->ticketsFilterOpen = true;
                $this->ticketsFilterInProgress = true;
                $this->ticketsFilterAll = false;
                $this->dispatch('refreshPortal');
                $this->dispatch('portal-screenshot-capture', ticketId: $record->id, label: $label);
            })
            ->modalHeading($label)
            ->modalSubmitActionLabel('Crear ticket')
            ->modalCancelActionLabel('Cancelar');
    }

    private function composeQuickTicketTitle(string $label): string
    {
        return sprintf('%s - PANTALLA %s', strtoupper($label), now()->format('Y-m-d'));
    }

    private function resolveSupportDepartmentId(): ?int
    {
        if (! DbSchema::hasTable('booking_departments')) {
            return null;
        }

        return BookingDepartment::query()
            ->when(DbSchema::hasColumn('booking_departments', 'has_tickets_service'), function (Builder $query): Builder {
                return $query->where('has_tickets_service', true);
            })
            ->where(function (Builder $query): void {
                $query->where('slug', 'soporte')
                    ->orWhere('name', 'like', '%soporte%')
                    ->orWhere('name', 'like', '%support%');
            })
            ->orderBy('name')
            ->value('id');
    }

    private function baseDocumentsQuery(int $taxistaId): Builder
    {
        return TaxistaDocument::query()
            ->select([
                'id',
                'taxista_user_id',
                'title',
                'document_type',
                'file_path',
                'status',
                'is_favorite',
                'uploaded_at',
                'created_at',
                'meta',
            ])
            ->where('taxista_user_id', $taxistaId)
            ->where(function (Builder $query): void {
                $query->whereNull('status')->orWhere('status', '!=', 'archivado');
            });
    }

    private function rememberPortalData(int $taxistaId, string $segment, array $context, callable $callback, int $ttlSeconds = 30): mixed
    {
        $version = $this->portalCacheVersion($taxistaId);
        $cacheKey = sprintf(
            'portal-taxista:%d:%d:%s:%s',
            $taxistaId,
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

    private function portalCacheVersion(int $taxistaId): int
    {
        return (int) Cache::get("portal-taxista:version:{$taxistaId}", 1);
    }

    private function flushPortalCache(?int $taxistaId = null): void
    {
        $taxistaId ??= PortalTaxistaContext::taxistaUserId();

        if (! $taxistaId) {
            $this->runtimeCache = [];

            return;
        }

        Cache::forever(
            "portal-taxista:version:{$taxistaId}",
            $this->portalCacheVersion($taxistaId) + 1,
        );

        $this->runtimeCache = [];
    }

    public function render(): View
    {
        $tab = $this->activeTab;

        $stats = $this->stats();

        $citas = ($tab === 'citas' || $tab === 'dashboard') ? $this->citas() : [];
        $tickets = ($tab === 'tickets' || $tab === 'dashboard') ? $this->tickets() : [];
        $gastos = ($tab === 'gastos') ? $this->gastos() : [];
        $cobros = ($tab === 'cobros') ? $this->cobros() : [];

        $documentosStats = ($tab === 'documentos' || $tab === 'dashboard') ? $this->documentosStats() : ['total' => 0, 'folders' => 0, 'favorites' => 0, 'types' => []];
        $documentosFavoritos = ($tab === 'documentos') ? $this->documentosFavoritos() : [];
        $documentosRecientes = ($tab === 'documentos') ? $this->documentosRecientes() : [];
        $documentosTodos = ($tab === 'documentos') ? $this->documentosTodos() : [];
        $documentosCarpeta = ($tab === 'documentos' && $this->docsFolder) ? $this->documentosCarpeta($this->docsFolder, $this->docsSegment) : [];
        $documentosCarpetaStats = ($tab === 'documentos' && $this->docsFolder) ? $this->documentosCarpetaStats($this->docsFolder) : ['count' => 0, 'favorites' => 0];
        $selectedDocumento = ($tab === 'documentos') ? $this->documentoSeleccionado($this->selectedDocumentId) : null;
        $spotlightResults = $this->shouldLoadSpotlightResults()
            ? $this->spotlightResults()
            : [];

        return view('livewire.portal-taxista-pro', [
            'stats' => $stats,
            'citas' => $citas,
            'documentos' => $documentosRecientes,
            'documentosStats' => $documentosStats,
            'documentosFavoritos' => $documentosFavoritos,
            'documentosRecientes' => $documentosRecientes,
            'documentosTodos' => $documentosTodos,
            'documentosCarpeta' => $documentosCarpeta,
            'documentosCarpetaStats' => $documentosCarpetaStats,
            'selectedDocumento' => $selectedDocumento,
            'tickets' => $tickets,
            'gastos' => $gastos,
            'cobros' => $cobros,
            'spotlightResults' => $spotlightResults,
            'taxista' => $this->resolveTaxista(),
        ]);
    }

    private function shouldLoadSpotlightResults(): bool
    {
        return $this->showSpotlight || strlen($this->spotlight) >= 2;
    }

    /**
     * @param  class-string  $resourceClass
     */
    private function safeResourceUrl(string $resourceClass, string $page, int $recordId, ?string $fallback = null): string
    {
        try {
            /** @phpstan-ignore-next-line */
            return $resourceClass::getUrl($page, ['record' => $recordId], panel: 'portal');
        } catch (\Throwable) {
            if (is_string($fallback) && $fallback !== '') {
                return $fallback;
            }

            return '#';
        }
    }

    /**
     * @return array<int, array{name:string, url:string}>
     */
    private function mapTicketAttachments(TaxistaTicket $ticket): array
    {
        $paths = $ticket->attachments ?? [];
        $names = $ticket->attachment_file_names ?? [];

        if (! is_array($paths) || $paths === []) {
            return [];
        }

        $attachments = [];

        foreach ($paths as $index => $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $name = $names[$path] ?? $names[$index] ?? basename($path);
            $url = $this->ticketAttachmentUrl($path);

            if ($url === null) {
                continue;
            }

            $attachments[] = [
                'name' => is_string($name) && $name !== '' ? $name : basename($path),
                'url' => $url,
            ];
        }

        return $attachments;
    }

    private function ticketAttachmentUrl(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return Storage::disk('public')->url($path);
    }
}
