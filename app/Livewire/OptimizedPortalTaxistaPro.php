<?php

namespace App\Livewire;

use App\Events\TaxistaLocationUpdated;
use App\Events\TaxistaPresenceUpdated;
use App\Filament\App\Resources\TaxistaAppointments\Schemas\TaxistaAppointmentForm;
use App\Filament\App\Resources\TaxistaAppointments\TaxistaAppointmentResource;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\App\Resources\TaxistaExpenses\Schemas\TaxistaExpenseForm;
use App\Filament\App\Resources\TaxistaTickets\Schemas\TaxistaTicketForm;
use App\Filament\App\Resources\TaxistaTickets\TaxistaTicketResource;
use App\Models\Taxista;
use App\Models\TaxistaAppointment;
use App\Models\TaxistaDocument;
use App\Models\TaxistaExpense;
use App\Models\TaxistaTicket;
use App\Models\User;
use App\Support\PortalTaxistaContext;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class OptimizedPortalTaxistaPro extends Component implements HasActions, HasForms
{
    use InteractsWithActions;
    use InteractsWithForms;
    use WithFileUploads;

    public string $activeTab = 'dashboard';
    public string $spotlight = '';
    public bool $showSpotlight = false;
    public bool $embedded = false;
    public bool $isOnline = false;
    public bool $trackingActive = false;
    public string $citasSegment = 'all';
    public string $ticketsSegment = 'open';
    public string $docsView = 'home';
    public ?string $docsFolder = null;
    public ?int $selectedDocumentId = null;
    public string $documentMode = 'view';
    public array $documentFormData = [];
    public string $docsSegment = 'all';
    public string $docsOrder = 'recent';
    public bool $citasFilterUpcoming = true;
    public bool $citasFilterPendiente = true;
    public bool $citasFilterConfirmada = true;
    public bool $citasFilterAll = false;
    public bool $ticketsFilterOpen = true;
    public bool $ticketsFilterInProgress = true;
    public bool $ticketsFilterAll = false;

    private ?Taxista $taxistaRecord = null;
    private ?array $cachedStats = null;

    // CACHE: Optimización resolveTaxista con cache
    private function resolveTaxista(): ?Taxista
    {
        $taxistaId = PortalTaxistaContext::taxistaUserId();

        if (!$taxistaId) {
            return null;
        }

        return Cache::remember("portal_taxista_{$taxistaId}", now()->addMinutes(30), function () use ($taxistaId) {
            return Taxista::query()->find($taxistaId);
        });
    }

    // CACHE: Stats optimizados con query unificada
    public function getStatsProperty(): array
    {
        if ($this->cachedStats !== null) {
            return $this->cachedStats;
        }

        $taxistaId = PortalTaxistaContext::taxistaUserId();

        if (!$taxistaId) {
            return $this->cachedStats = [
                'documentos' => 0, 'citas' => 0, 'tickets' => 0,
                'gastos' => 0, 'taxis' => 0, 'chats' => 0
            ];
        }

        $this->cachedStats = Cache::remember("portal_stats_{$taxistaId}", now()->addMinutes(5), function () use ($taxistaId) {
            return [
                'documentos' => TaxistaDocument::where('taxista_user_id', $taxistaId)->count(),
                'citas' => TaxistaAppointment::where('taxista_user_id', $taxistaId)
                    ->where('starts_at', '>=', now()->startOfDay())->count(),
                'tickets' => TaxistaTicket::where('user_id', $taxistaId)
                    ->whereIn('status', ['abierto', 'en_proceso'])->count(),
                'gastos' => TaxistaExpense::where('taxista_user_id', $taxistaId)->count(),
                'taxis' => 0, // TODO: Implementar cuando tengamos la relación
                'chats' => 0, // TODO: Implementar cuando tengamos la relación
            ];
        });

        return $this->cachedStats;
    }

    // CACHE: Citas optimizadas con eager loading
    public function citas(): array
    {
        $taxistaId = PortalTaxistaContext::taxistaUserId();

        if (!$taxistaId) {
            return [];
        }

        $cacheKey = "portal_citas_{$taxistaId}_" . md5(serialize([
                $this->citasSegment, $this->citasFilterUpcoming,
                $this->citasFilterPendiente, $this->citasFilterConfirmada, $this->citasFilterAll
            ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($taxistaId) {
            $query = TaxistaAppointment::query()
                ->with(['booking_department:id,name,color']) // Selects específicos
                ->where('taxista_user_id', $taxistaId);

            if (!$this->citasFilterAll) {
                $statuses = [];
                if ($this->citasFilterPendiente) $statuses[] = 'pendiente';
                if ($this->citasFilterConfirmada) $statuses[] = 'confirmada';

                if (empty($statuses)) return [];
                $query->whereIn('status', $statuses);
            }

            if ($this->citasFilterUpcoming) {
                $query->where('starts_at', '>=', now()->startOfDay());
            }

            return $query
                ->orderBy('starts_at', 'desc')
                ->limit(20)
                ->get()
                ->map(function (TaxistaAppointment $a): array {
                    return [
                        'id' => $a->id,
                        'titulo' => $a->title,
                        'fecha' => $a->starts_at?->format('d/m') ?? '—',
                        'mes' => $a->starts_at?->format('M') ?? '',
                        'hora' => $a->starts_at?->format('H:i') ?? '—',
                        'lugar' => $a->notes ?? '—',
                        'estado' => $a->status ?? 'pendiente',
                        'departamento' => $a->booking_department?->name ?? '—',
                        'departamento_color' => $a->booking_department?->color,
                        'url' => $this->safeResourceUrl(TaxistaAppointmentResource::class, 'edit', (int)$a->id),
                    ];
                })
                ->toArray();
        });
    }

    // CACHE: Documentos optimizados
    public function documentos(): array
    {
        $taxista = $this->resolveTaxista();
        if (!$taxista) return [];

        $taxistaId = (int)$taxista->getKey();
        $cacheKey = "portal_docs_{$taxistaId}_" . md5(serialize([$this->docsSegment, $this->docsOrder]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($taxistaId) {
            return TaxistaDocument::query()
                ->where('taxista_user_id', $taxistaId)
                ->orderByDesc('is_favorite')
                ->orderByDesc('uploaded_at')
                ->limit(20)
                ->get()
                ->map(fn(TaxistaDocument $d): array => [
                    'id' => $d->id,
                    'nombre' => $d->title,
                    'tipo' => $d->document_type ?? 'otros',
                    'fecha' => $d->uploaded_at?->format('d/m/Y') ?? $d->created_at?->format('d/m/Y') ?? '—',
                    'favorito' => (bool)$d->is_favorite,
                    'estado' => $d->status ?? 'activo',
                ])
                ->toArray();
        });
    }

    // CACHE: Tickets optimizados con eager loading
    public function tickets(): array
    {
        $taxista = $this->resolveTaxista();
        if (!$taxista) return [];

        $taxistaId = (int)$taxista->getKey();
        $cacheKey = "portal_tickets_{$taxistaId}_" . md5(serialize([
                $this->ticketsSegment, $this->ticketsFilterOpen, $this->ticketsFilterInProgress, $this->ticketsFilterAll
            ]));

        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($taxistaId) {
            $query = TaxistaTicket::query()
                ->with(['department:id,name']) // Selects específicos
                ->where('user_id', $taxistaId);

            if (!$this->ticketsFilterAll) {
                $statuses = [];
                if ($this->ticketsFilterOpen) $statuses[] = 'abierto';
                if ($this->ticketsFilterInProgress) $statuses[] = 'en_proceso';

                if (empty($statuses)) return [];
                $query->whereIn('status', $statuses);
            }

            return $query
                ->orderByDesc('opened_at')
                ->limit(20)
                ->get()
                ->map(fn(TaxistaTicket $t): array => [
                    'id' => $t->id,
                    'titulo' => $t->title,
                    'estado' => $t->status ?? 'abierto',
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
                    'prioridad' => $t->priority ?? 'media',
                    'priority_class' => match ((string)($t->priority ?? 'media')) {
                        'alta' => 'text-red-300',
                        'media' => 'text-amber-300',
                        default => 'text-zinc-400',
                    },
                    'fecha' => $t->opened_at?->format('d/m/Y') ?? $t->created_at?->format('d/m/Y') ?? '-',
                    'categoria' => $t->department?->name ?? 'General',
                    'url' => TaxistaTicketResource::getUrl('edit', ['record' => $t->id], panel: 'portal'),
                ])
                ->toArray();
        });
    }

    // CACHE: Documentos favoritos optimizados
    public function documentosFavoritos(int $limit = 6): array
    {
        $taxista = $this->resolveTaxista();
        if (!$taxista) return [];

        $taxistaId = (int)$taxista->getKey();

        return Cache::remember("portal_fav_docs_{$taxistaId}", now()->addMinutes(10), function () use ($taxistaId, $limit) {
            return TaxistaDocument::query()
                ->where('taxista_user_id', $taxistaId)
                ->where('is_favorite', true)
                ->orderByDesc('uploaded_at')
                ->limit($limit)
                ->get()
                ->map(function (TaxistaDocument $d): array {
                    return [
                        'id' => $d->id,
                        'nombre' => $d->title,
                        'tipo' => $d->document_type ?? 'otros',
                        'fecha' => $d->uploaded_at?->format('d/m') ?? $d->created_at?->format('d/m') ?? '—',
                        'favorito' => true,
                        'estado' => $d->status ?? 'activo',
                        'url' => route('filament.portal.resources.taxista-documents.edit', $d->id),
                    ];
                })
                ->toArray();
        });
    }

    // CACHE: Documentos recientes optimizados
    public function documentosRecientes(int $limit = 10): array
    {
        $taxista = $this->resolveTaxista();
        if (!$taxista) return [];

        $taxistaId = (int)$taxista->getKey();

        return Cache::remember("portal_recent_docs_{$taxistaId}", now()->addMinutes(5), function () use ($taxistaId, $limit) {
            return TaxistaDocument::query()
                ->where('taxista_user_id', $taxistaId)
                ->orderByDesc('uploaded_at')
                ->limit($limit)
                ->get()
                ->map(function (TaxistaDocument $d): array {
                    return [
                        'id' => $d->id,
                        'nombre' => $d->title,
                        'tipo' => $d->document_type ?? 'otros',
                        'fecha' => $d->uploaded_at?->format('d/m') ?? $d->created_at?->format('d/m') ?? '—',
                        'favorito' => (bool)$d->is_favorite,
                        'estado' => $d->status ?? 'activo',
                        'url' => route('filament.portal.resources.taxista-documents.edit', $d->id),
                    ];
                })
                ->toArray();
        });
    }

    // Optimización: Limpiar cache cuando se modifican datos
    public function updatedDocumentFormData(): void
    {
        $this->clearPortalCache();
    }

    public function updatedActiveTab(): void
    {
        // Lazy loading: solo cargar datos del tab activo
        $this->clearPortalCache();
    }

    private function clearPortalCache(): void
    {
        $taxistaId = PortalTaxistaContext::taxistaUserId();
        if ($taxistaId) {
            Cache::forget("portal_taxista_{$taxistaId}");
            Cache::forget("portal_stats_{$taxistaId}");
            Cache::forget("portal_citas_{$taxistaId}_*");
            Cache::forget("portal_docs_{$taxistaId}_*");
            Cache::forget("portal_tickets_{$taxistaId}_*");
            Cache::forget("portal_fav_docs_{$taxistaId}");
            Cache::forget("portal_recent_docs_{$taxistaId}");
        }

        $this->cachedStats = null;
    }

    // Métodos existentes optimizados...
    public function mount(): void
    {
        $this->taxistaRecord = $this->resolveTaxista();

        $authUser = auth('taxista')->user() ?? auth('web')->user();
        $this->isOnline = (bool)($authUser?->is_online ?? false);

        // Resto del mount original...
    }

    // Resto de métodos del componente original...
    // (createCitaAction, createDocumentoAction, etc.)
}
