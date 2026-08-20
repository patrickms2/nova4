<?php

namespace App\Filament\Portal\Pages;

use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Models\Taxista;
use App\Models\TaxistaDocument;
use BackedEnum;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Str;

class TaxistaDocuments extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-folder';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $slug = 'documentos';

    protected static \UnitEnum|string|null $navigationGroup = 'Taxista';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.portal.pages.taxista-documents';

    public ?int $taxistaId = null;

    public ?string $selectedDocumentType = null;

    public bool $showFavoriteDocuments = true;

    private ?Taxista $cachedTaxistaRecord = null;

    private ?int $cachedTaxistaRecordId = null;

    private ?SupportCollection $cachedDocumentFolders = null;

    private ?Collection $cachedFavoriteDocuments = null;

    /**
     * @var array<string, Collection<int, TaxistaDocument>>
     */
    private array $cachedSelectedDocuments = [];

    public static function canAccess(): bool
    {
        $user = auth()->user() ?? auth('web')->user();

        if (!$user) {
            return false;
        }

        return Taxista::query()->whereKey($user->id)->exists();
    }

    public function mount(): void
    {
        $this->taxistaId = $this->resolvePortalTaxistaId();
        $this->cachedTaxistaRecord = null;
        $this->cachedTaxistaRecordId = null;
        $this->cachedDocumentFolders = null;
        $this->cachedFavoriteDocuments = null;
        $this->cachedSelectedDocuments = [];
    }

    public function documentFolders(): SupportCollection
    {
        if ($this->cachedDocumentFolders !== null) {
            return $this->cachedDocumentFolders;
        }

        $taxista = $this->taxista();

        if (!$taxista) {
            $this->cachedDocumentFolders = collect();

            return $this->cachedDocumentFolders;
        }

        $counts = TaxistaDocument::query()
            ->where('taxista_user_id', $taxista->id)
            ->selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        $this->cachedDocumentFolders = $this->mapDocumentFolders($counts);

        return $this->cachedDocumentFolders;
    }

    public function selectedDocumentFolder(): ?array
    {
        if (!$this->selectedDocumentType) {
            return null;
        }

        return $this->documentFolders()
            ->first(fn(array $folder): bool => $folder['type'] === $this->selectedDocumentType);
    }

    public function selectedDocuments(): Collection
    {
        $taxista = $this->taxista();

        if (!$taxista || !$this->selectedDocumentType) {
            return new Collection();
        }

        if (array_key_exists($this->selectedDocumentType, $this->cachedSelectedDocuments)) {
            return $this->cachedSelectedDocuments[$this->selectedDocumentType];
        }

        $this->cachedSelectedDocuments[$this->selectedDocumentType] = TaxistaDocument::query()
            ->with('department')
            ->where('taxista_user_id', $taxista->id)
            ->where('document_type', $this->selectedDocumentType)
            ->orderByDesc('is_favorite')
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->get();

        return $this->cachedSelectedDocuments[$this->selectedDocumentType];
    }

    public function favoriteDocuments(): Collection
    {
        if ($this->cachedFavoriteDocuments !== null) {
            return $this->cachedFavoriteDocuments;
        }

        $taxista = $this->taxista();

        if (!$taxista) {
            $this->cachedFavoriteDocuments = new Collection();

            return $this->cachedFavoriteDocuments;
        }

        $this->cachedFavoriteDocuments = TaxistaDocument::query()
            ->where('taxista_user_id', $taxista->id)
            ->where('is_favorite', true)
            ->orderByDesc('uploaded_at')
            ->orderByDesc('created_at')
            ->limit(8)
            ->get();

        return $this->cachedFavoriteDocuments;
    }

    public function openDocumentFolder(string $documentType): void
    {
        if (!array_key_exists($documentType, $this->documentTypeOptions())) {
            return;
        }

        $this->selectedDocumentType = $documentType;
    }

    public function backToDocumentFolders(): void
    {
        $this->selectedDocumentType = null;
    }

    public function openFavoriteDocuments(): void
    {
        $this->showFavoriteDocuments = true;
    }

    public function closeFavoriteDocuments(): void
    {
        $this->showFavoriteDocuments = false;
    }

    public function toggleFavorite(int $documentId): void
    {
        $taxista = $this->taxista();

        if (!$taxista) {
            return;
        }

        $document = TaxistaDocument::query()
            ->where('taxista_user_id', $taxista->id)
            ->find($documentId);

        if (!$document) {
            return;
        }

        $document->update([
            'is_favorite' => !$document->is_favorite,
        ]);

        $this->cachedFavoriteDocuments = null;
        $this->cachedSelectedDocuments = [];
    }

    public function updateDocumentType(int $documentId, string $documentType): void
    {
        $taxista = $this->taxista();

        if (!$taxista) {
            return;
        }

        if (!array_key_exists($documentType, $this->documentTypeOptions())) {
            Notification::make()
                ->title('Tipo de documento no valido.')
                ->danger()
                ->send();

            return;
        }

        $document = TaxistaDocument::query()
            ->where('taxista_user_id', $taxista->id)
            ->find($documentId);

        if (!$document) {
            return;
        }

        $document->update([
            'document_type' => $documentType,
        ]);

        $this->cachedDocumentFolders = null;
        $this->cachedFavoriteDocuments = null;
        $this->cachedSelectedDocuments = [];
    }

    public function createDocumentUrl(): string
    {
        return TaxistaDocumentResource::getUrl('create', panel: 'portal');
    }

    private function taxista(): ?Taxista
    {
        if (!$this->taxistaId) {
            return null;
        }

        if ($this->cachedTaxistaRecordId !== $this->taxistaId) {
            $this->cachedTaxistaRecord = Taxista::query()->find($this->taxistaId);
            $this->cachedTaxistaRecordId = $this->taxistaId;
        }

        return $this->cachedTaxistaRecord;
    }

    private function resolvePortalTaxistaId(): ?int
    {
        $user = auth('taxista')->user() ?? auth('web')->user();

        if (!$user) {
            return null;
        }

        $userAsTaxista = Taxista::query()->whereKey($user->id)->value('id');

        return $userAsTaxista ? (int)$userAsTaxista : null;
    }

    public function mapDocumentFolders(SupportCollection $counts): SupportCollection
    {
        $knownDocumentTypes = array_keys($this->documentTypeOptions());
        $detectedDocumentTypes = $counts
            ->keys()
            ->filter(fn(mixed $type): bool => is_string($type) && $type !== '')
            ->all();

        $orderedDocumentTypes = array_values(array_unique([
            ...$knownDocumentTypes,
            ...$detectedDocumentTypes,
        ]));

        return collect($orderedDocumentTypes)
            ->map(function (string $type) use ($counts): array {
                return [
                    'type' => $type,
                    'label' => $this->documentTypeOptions()[$type] ?? Str::headline($type),
                    'count' => (int)($counts[$type] ?? 0),
                ];
            })
            ->filter(fn(array $folder): bool => $folder['count'] > 0)
            ->values();
    }

    /**
     * @return array<string, string>
     */
    public function documentTypeOptions(): array
    {
        return [
            'nomina' => 'Nominas',
            'impuesto' => 'Impuestos',
            'certificado' => 'Certificados',
            'seguro' => 'Seguros',
            'otros' => 'Otros',
        ];
    }
}
