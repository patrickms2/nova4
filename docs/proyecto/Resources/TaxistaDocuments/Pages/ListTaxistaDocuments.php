<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Filament\App\Resources\TaxiDocumentTypes\Pages\ListTaxiDocumentTypes;
use App\Filament\App\Pages\TaxistaImportIssues;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentZipForm;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Models\TaxistaDocument;
use App\Services\Taxistas\TaxistaDocumentBulkImporter;
use App\Services\Taxistas\TaxistaDocumentMultipageImporter;
use App\Support\PortalTaxistaContext;
use App\Support\TaxistaDocumentTypes;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Components\RenderHook;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Url;
use Archilex\AdvancedTables\AdvancedTables;
use Filament\Support\Enums\Width;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\PdfToText\Pdf;
use setasign\Fpdi\Fpdi;
use ZipArchive;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Facades\Filament;

class ListTaxistaDocuments extends ListRecords
{
    use AdvancedTables;

    protected static string $resource = TaxistaDocumentResource::class;

    protected static ?string $title = 'Documentos';

    #[Url(as: 'folder')]
    public ?string $selectedDocumentType = null;

    public function mount(): void
    {
        parent::mount();

        $this->selectedDocumentType = $this->normalizeDocumentType($this->selectedDocumentType);
    }

    public function getTitle(): string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Mis Documentos';
        }

        return 'Documentos';
    }

    public function getSubheading(): ?string
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return 'Documentacion del taxista.';
        }

        return null;
    }

    public function getTabs(): array
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return [];
        }

        $tabs = [
            'all' => Tab::make()
                ->label('Todos')
                ->badge(fn(): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaDocument::query())->count()),
        ];

        foreach (TaxistaDocumentTypes::options() as $type => $label) {
            $tabs[$type] = Tab::make()
                ->label($label)
                ->badge(fn(): int => PortalTaxistaContext::scopeTaxistaRecordQuery(TaxistaDocument::query())
                    ->where('document_type', $type)
                    ->count())
                ->modifyQueryUsing(fn(Builder $query): Builder => PortalTaxistaContext::scopeTaxistaRecordQuery($query)
                    ->where('document_type', $type));
        }

        return $tabs;
    }

    protected function getHeaderActions(): array
    {
        $createAction = CreateAction::make()
            ->icon('heroicon-s-document-plus')
            ->color('danger')
            ->hiddenLabel(true)
            //->slideOver()
            ->Button()
            ->createAnother(false)
            ->modalWidth(Width::ExtraLarge)
            ->modalSubmitActionLabel('Guardar')
            ->modalCancelActionLabel('Cerrar')
            ->label('Nuevo');
        $createActionZip = Action::make('createZip')
            ->icon('heroicon-o-archive-box-arrow-down')
            ->color('danger')
            ->label('Nuevo Zip')
            ->modalWidth(Width::ExtraLarge)
            ->form(TaxistaDocumentZipForm::configure())
            ->modalSubmitAction(false)  // Eliminar botón de submit
            ->modalCancelActionLabel('Cerrar');
        $Kanban = Action::make('Kanban')
            ->hiddenLabel(true)
            ->icon('icon-kanban')
            ->color('danger')
            ->url(function () {
                // Extraer tenant ID de la URL actual
                $currentUrl = request()->url();
                $tenantId = '1'; // fallback
                
                // Buscar el patrón /app/team/{tenant}/ en la URL actual
                if (preg_match('/\/app\/team\/([^\/]+)\//', $currentUrl, $matches)) {
                    $tenantId = $matches[1];
                }
                
                return '/app/team/' . $tenantId . '/kanban-view';
            });

        $documentTypesAction = Action::make('document_types')
            ->color('danger')
            ->icon('heroicon-o-squares-plus')
            ->label('Tipos');

        $importIssuesAction = Action::make('import_issues')
            ->color('danger')
            ->icon('heroicon-o-exclamation-triangle')
            ->label('Alertas importacion')
            ->url(fn (): string => TaxistaImportIssues::getUrl(panel: 'app', tenant: Filament::getTenant()));

        if (!PortalTaxistaContext::isPortalPanel()) {
            $documentTypesAction->url(fn(): string => ListTaxiDocumentTypes::getUrl(panel: 'app'));
        }

        if (PortalTaxistaContext::isPortalPanel()) {
            $createAction
                ->icon('heroicon-o-plus')
                ->hiddenLabel();
        }

        $bulkImportAction = Action::make('bulkImport')
            ->label('Importacion masiva')
            ->visible(false)
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->form([
                FileUpload::make('csv_path')
                    ->label('Archivo CSV')
                    ->disk('local')
                    ->directory('imports/taxista-documents')
                    ->acceptedFileTypes(['text/csv', 'text/plain'])
                    ->required(),

                Toggle::make('skip_duplicates')
                    ->label('Omitir duplicados por taxista + ruta')
                    ->default(true),
            ])
            ->action(function (array $data, TaxistaDocumentBulkImporter $importer): void {
                $csvPath = (string)($data['csv_path'] ?? '');

                if ($csvPath === '') {
                    return;
                }

                $summary = $importer->importFromCsvPath(
                    csvPath: storage_path('app/' . ltrim($csvPath, '/')),
                    uploadedByUserId: auth()->id(),
                    skipDuplicates: (bool)($data['skip_duplicates'] ?? true),
                );

                Notification::make()
                    ->title('Importacion completada')
                    ->body(
                        sprintf(
                            'Procesados: %d | Creados: %d | Actualizados: %d | Omitidos: %d',
                            $summary['processed'],
                            $summary['created'],
                            $summary['updated'],
                            $summary['skipped'],
                        )
                    )
                    ->success()
                    ->send();

                if (count($summary['errors']) > 0) {
                    Notification::make()
                        ->title('Importacion con incidencias')
                        ->body('Filas con error: ' . count($summary['errors']))
                        ->warning()
                        ->send();
                }

                Storage::disk('local')->delete($csvPath);
            });

        $multipageImportGroup = ActionGroup::make([
            $this->makeMultipageImportAction('agencias_multipagina', 'Agencias', 'agencias'),
            $this->makeMultipageImportAction('cuotas_multipagina', 'Cuotas', 'cuotas'),
            $this->makeMultipageImportAction('nominas_multipagina', 'Nominas', 'nomina'),
            $this->makeMultipageImportAction('repuestos_multipagina', 'Repuestos', 'repuestos'),
            $this->makeMultipageImportAction('seguros_multipagina', 'Seguros', 'seguro'),
        ])
            ->label('Multipagina')
            ->button()
            ->icon('heroicon-o-arrow-up-tray')
            ->color('danger');

        if (PortalTaxistaContext::isPortalPanel()) {
            return [
                Action::make('folders')
                    ->label('Carpetas')
                    ->icon('heroicon-o-squares-2x2')
                    ->color('gray')
                    ->tooltip('Carpetas por tipo')
                    ->action(function (): void {
                        $this->backToDocumentFolders();
                    }),
                $createAction,
            ];
        }

        return [
            $createAction,
            $createActionZip,
            $multipageImportGroup,
            $importIssuesAction,
            $Kanban,
            $documentTypesAction,
            $bulkImportAction,
        ];
    }

    private function makeMultipageImportAction(string $name, string $label, string $documentType): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-s-squares-plus')
            ->color('warning')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->form([
                Toggle::make('skip_duplicates')
                    ->label('Omitir duplicados (por taxista + tipo + referencia)')
                    ->default(true),

                FileUpload::make('pdf')
                    ->label("PDF multipagina ({$label})")
                    ->disk('local')
                    ->directory('imports/taxista-documents/multipage')
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->previewable(false)
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (mixed $state, \Filament\Schemas\Components\Utilities\Set $set, \Filament\Schemas\Components\Utilities\Get $get) use ($documentType): void {
                        if ($state === null || is_string($state)) {
                            $pdfPath = is_string($state) && $state !== ''
                                ? storage_path('app/' . ltrim($state, '/'))
                                : null;
                        } else {
                            $pdfPath = method_exists($state, 'getPathname') ? $state->getPathname() : null;
                        }

                        if (!$pdfPath || !is_file($pdfPath)) {
                            return;
                        }

                        $importer = app(TaxistaDocumentMultipageImporter::class);
                        $summary = $importer->importFromPdfPath(
                            pdfPath: $pdfPath,
                            documentType: $documentType,
                            uploadedByUserId: auth()->id(),
                            skipDuplicates: (bool)$get('skip_duplicates'),
                            forcedTaxistaUserId: null,
                        );

                        $logMessage = $summary['log_path'] !== ''
                            ? "\n\nLog: storage/app/{$summary['log_path']}"
                            : '';

                        $set('notes', $summary['notes'] . $logMessage);

                        $notification = Notification::make()
                            ->title('Importacion multipagina completada')
                            ->body($summary['notes'] . $logMessage);

                        if ($summary['errors'] !== []) {
                            $notification->warning();
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    }),

                Textarea::make('notes')
                    ->label('Notas / incidencias')
                    ->rows(4)
                    ->dehydrated(false)
                    ->placeholder('Sube un PDF para procesar...'),
            ]);
    }

    protected function getHeaderWidgets(): array
    {
        return TaxistaDocumentResource::getWidgets();
    }

    public function content(Schema $schema): Schema
    {
        if (!PortalTaxistaContext::isPortalPanel()) {
            return parent::content($schema);
        }

        return $schema
            ->components([
                $this->getTabsContentComponent(),
                View::make('filament.portal.resources.taxista-documents.folder-explorer')
                    ->viewData([
                        'selectedDocumentType' => $this->selectedDocumentType,
                        'selectedDocumentLabel' => $this->selectedDocumentLabel(),
                        'folders' => $this->portalDocumentFolders(),
                        'metrics' => $this->portalDocumentMetrics(),
                    ]),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_BEFORE),
                EmbeddedTable::make()
                    ->hidden(fn(): bool => !$this->hasSelectedDocumentFolder()),
                RenderHook::make(PanelsRenderHook::RESOURCE_PAGES_LIST_RECORDS_TABLE_AFTER),
            ]);
    }

    public function table(Table $table): Table
    {
        if (!PortalTaxistaContext::isPortalPanel()) {
            return $table;
        }

        return $table
            ->modifyQueryUsing(function (Builder $query): Builder {
                if (!$this->hasSelectedDocumentFolder()) {
                    return $query->whereRaw('1 = 0');
                }

                return $query->where('document_type', $this->selectedDocumentType);
            });
    }

    public function hasSelectedDocumentFolder(): bool
    {
        return $this->selectedDocumentType !== null;
    }

    public function openDocumentFolder(string $documentType): void
    {
        $normalizedDocumentType = $this->normalizeDocumentType($documentType);

        if ($normalizedDocumentType === null) {
            return;
        }

        $this->selectedDocumentType = $normalizedDocumentType;
    }

    public function backToDocumentFolders(): void
    {
        $this->selectedDocumentType = null;
    }

    /**
     * @return array<int, array{type: string, label: string, count: int}>
     */
    public function portalDocumentFolders(): array
    {
        $counts = TaxistaDocumentResource::getEloquentQuery()
            ->selectRaw('document_type, COUNT(*) as total')
            ->groupBy('document_type')
            ->pluck('total', 'document_type');

        return collect($this->documentTypeOptions())
            ->map(fn(string $label, string $type): array => [
                'type' => $type,
                'label' => $label,
                'count' => (int)($counts[$type] ?? 0),
            ])
            ->filter(fn(array $folder): bool => $folder['count'] > 0)
            ->values()
            ->all();
    }

    /**
     * @return array{documents: int, folders: int, favorites: int}
     */
    public function portalDocumentMetrics(): array
    {
        $query = TaxistaDocumentResource::getEloquentQuery();
        $documents = (clone $query)->count();
        $folders = count($this->portalDocumentFolders());
        $favorites = (clone $query)->where('is_favorite', true)->count();

        return [
            'documents' => $documents,
            'folders' => $folders,
            'favorites' => $favorites,
        ];
    }

    public function selectedDocumentLabel(): ?string
    {
        if (!$this->hasSelectedDocumentFolder()) {
            return null;
        }

        return $this->documentTypeOptions()[$this->selectedDocumentType] ?? null;
    }

    /**
     * @return array<string, string>
     */
    protected function documentTypeOptions(): array
    {
        return TaxistaDocumentTypes::options();
    }

    protected function normalizeDocumentType(?string $documentType): ?string
    {
        if ($documentType === null || $documentType === '') {
            return null;
        }

        if (!array_key_exists($documentType, $this->documentTypeOptions())) {
            return null;
        }

        return $documentType;
    }
}
