<?php

namespace App\Filament\App\Resources\BookingDepartments\Pages;

use App\Filament\App\Resources\BookingDepartments\BookingDepartmentResource;
use App\Filament\App\Resources\TaxiDocumentTypes\Pages\ListTaxiDocumentTypes;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentZipForm;
use App\Filament\App\Resources\TaxistaDocuments\Tables\TaxistaDocumentsTable;
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
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

class ManageDepartmentDocuments extends ManageRelatedRecords
{
    protected static string $resource = BookingDepartmentResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationLabel = 'Documentos';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?int $navigationSort = 7;


    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->documents()->count();
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
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
            $Kanban,
            $documentTypesAction,
            $bulkImportAction,
        ];
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Documentos gestionados por este departamento.';
    }

    public function form(Schema $schema): Schema
    {
        return TaxistaDocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TaxistaDocumentsTable::configure($table)
            ->relationship(null)
            ->query(\App\Models\TaxistaDocument::query());
    }
}
