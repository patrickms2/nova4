<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Enums\DocumentosTipo;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Filament\Clusters\Taxistas\Pdfs\PdfResource;
use App\Filament\Clusters\Taxistas\Pdfs\Schemas\PdfForm;
use App\Filament\Clusters\Taxistas\Pdfs\Schemas\PdfForm2;
use App\Models\TaxiCentral\Document;
use App\Models\Taxi\TipoDoc;
use App\Models\Taxi\TipoUsuario;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Kanban;
use Asmit\AdvancedKanban\Pages\KanbanPage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\HasTabs;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Schemas\Schema as Form;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;

final class ManageDocumentosKanbanTaxista extends KanbanPage
{
    use HasTabs;

    protected static string|null|\BackedEnum $navigationIcon = Heroicon::DocumentText;
    protected static string|null|\UnitEnum $navigationGroup = null;
    protected static ?string $navigationParentItem = 'Documentos';
    protected static ?string $navigationLabel = 'Kanban';
    protected static ?int $navigationSort = 52;

    public $tiposdoc;
    public ?array $data = [];

    protected static string $model = Document::class;

    protected static string $resource = PdfResource::class;

    protected static string $statusEnum = DocumentosTipo::class;

    protected static string $recordTitleAttribute = 'referencia';
    protected static null|string $title = 'Documentos - Kanban';

    protected static string $recordStatusAttribute = 'tipodoc';

    protected static string $columnHeaderComponent = 'advanced-kanban::column-header_doc';

    protected static string $cardComponent = 'advanced-kanban::card_doc';

    protected static string $relationship = 'documentos';

    public function with(): array
    {
        return [
            'tiposdoc' => $this->tiposdoc(),
        ];
    }

    public function addAction(): \Asmit\AdvancedKanban\RecordAction\Action
    {
        return Action::make('docs')
            ->schema(function (array $arguments): array {
                return [
                    TextInput::make('title')->default($arguments['id']),
                ];
            });
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $cita = Document::create($data);

        // Save the relationships from the form to the post after it is created.
        $this->form->model($cita)->saveRelationships();
        $this->form->fill();

    }

    #[Computed]
    public function tiposdoc()
    {
        return TipoDoc::query()
            ->withWhereHas(
                'documentos', function ($query) {
                $query->where('taxi_documents.estado', 1);
            }, '>',
                1
            )
            ->where('estado', 1)
            ->orderBy('tipos_docs.order', 'asc')
            ->paginate(20);
    }

    public function kanban(Kanban $kanban): Kanban
    {

        $descripciones = [
            'todos' => 'Vista general de toda la documentación',
            'repuestos' => 'Facturas y pedidos de recambios',
            'seguros' => 'Pólizas y recibos de vehículos',
            'nominas' => 'Recibos de salarios mensuales',
            'impuestos' => 'Liquidaciones y tributos',
            'varios' => 'Documentación diversa',
            'servicios' => 'Mantenimientos y revisiones',
            'cuotas' => 'Pagos recurrentes y suscripciones',
        ];
        $tipos = $this->tiposdoc();
        $columnas = [];
        $slug = '';
        $documentTypeId = '';
        foreach ($tipos as $tipo) {
            if ($tipo['slug'] !== null) {
                $slug = $tipo['slug'];
                $documentTypeId = $tipo['id'];
            }
            // echo $tipo['slug'].'heroicon-s-'.DocumentosTipo::getIconBySlug($tipo['slug']).'<br>';
            $columnas[] = KanbanColumn::make($slug)
                ->hidden(false)
                ->iconcolor($tipo['color'])
                ->icon($tipo['icono'])
                ->label(fn() => ucfirst($tipo['nombre']))
                ->description('Gestión de ' . $tipo['nombre'])
                ->modifyRecordQueryUsing(function ($query) use ($documentTypeId) {
                    return $query->where('taxi_documents.document_type_id', $documentTypeId)->orderBy('taxi_documents.id', 'desc');
                });
        }

        $kanban22 = $kanban
            ->model(Document::class)
            ->statusField('tipodoc')
            ->titleField('titulo')
            ->descriptionField('notas')
            ->searchableFields(['referencia', 'mes', 'tipodoc', 'favorito'])
            ->enableLoadingIndicator()
            ->enableFilterIndicator()
            ->columnHeaderActions([
                \Asmit\AdvancedKanban\Actions\CreateAction::make('nueva')
                    ->schema(function (Schema $schema, array $arguments) {
                        $schema = PdfForm2::configure($schema, $arguments['status']);

                        return $schema;
                    })
                    ->action(function ($arguments, $data) {
                        $data['usuario_id'] = auth()->id();
                        $doc = Document::create($data);
                    })
                    ->icon(Heroicon::OutlinedPlus)
                    ->hiddenLabel()
                    ->link(),

                Action::make('Ocultar')
                    ->schema(function (array $arguments): array {
                        return [
                            ToggleButtons::make('tipodoc')
                                ->extraAttributes(['class' => 'text-xl',
                                    'style' => 'font-weight: bold;']) // Aplica negritas                                                        ->inline()
                                ->inline()
                                ->multiple()
                                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                    $col = [];
                                    $col = $this->getKanban()->getColumns();
                                    foreach ($col as $key) {
                                        if (in_array($key->getStatus(), $state)) {
                                            $key->hidden(true);
                                        }
                                    }
                                    // dd($col);
                                    if (!$state | $state === null) {
                                        return;
                                    }
                                })
                                ->reactive()
                                ->options(DocumentosTipo::getOptions()),
                        ];
                    })
                    ->action(function ($arguments, $data) {
                        // dd($arguments, $data);
                    })
                    ->icon(Heroicon::OutlinedPlus)
                    ->hiddenLabel()
                    ->link(),
            ])
            ->filterFormSchema([
                Select::make('usuario_id')
                    ->label('Usuarios')
                    ->multiple(true)
                    ->searchable()
                    ->preload()
                    ->options(static function () {
                        $tiposUsuarios = TipoUsuario::with('usuarios') // Asegúrate de tener una relación en el modelo TipoUsuario con usuarios
                        ->orderBy('nombre')
                            ->where('estado', 1)
                            ->get();

                        // Agrupar por tipo de usuario
                        $grupoDeOpciones = [];
                        foreach ($tiposUsuarios as $tipoUsuario) {
                            $opciones = $tipoUsuario->usuarios->pluck('nombre', 'id')->toArray();
                            if (!empty($opciones)) {
                                $grupoDeOpciones[$tipoUsuario->nombre] = $opciones;
                            }
                        }

                        return $grupoDeOpciones;
                    })
                    ->relationship('usuario', 'nombre'),

                Select::make('document_type_id')
                    ->label('Tipo de Documento')
                    ->relationship('documentType', 'name')
                    ->multiple(true)
                    ->preload(),

                Select::make('departamento_id')
                    ->label('Departamento')
                    ->multiple(true)
                    ->relationship('departamento', 'nombre')
                    ->preload(),

                /*Select::make('usuario_id')
                    ->label('Usuario')
                    ->relationship('usuario', 'nombre')
                    ->required()
                    ->default(fn (Get $get) => auth()->user()->id)
                    ->live()
                    ->searchable()
                    ->reactive(),*/
            ])
            ->applyFiltersUsing(function (Builder $query, array $filters): Builder {
                if (!empty($filters['favorito'])) {
                    $query->where('favorito', $filters['favorito']);
                }
                if (!empty($filters['document_type_id'])) {
                    $query->whereIn('document_type_id', $filters['document_type_id']);
                }
                if (!empty($filters['departamento_id'])) {
                    $query->where('departamento_id', $filters['departamento_id']);
                }
                if (!empty($filters['usuario_id'])) {
                    $query->whereIn('usuario_id', $filters['usuario_id']);
                }

                return $query;
            })
            ->recordActions([
                Action::make('view_file')
                    ->label('')
                    ->tooltip('Ver documento')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(Document $record): ?string => $this->resolveDocumentViewUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn(Document $record): bool => filled($this->resolveDocumentViewUrl($record))),
                Action::make('download_file')
                    ->label('')
                    ->tooltip('Descargar')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->action(function (Document $record) {
                        $path = $this->resolveDocumentStoragePath($record);

                        if (!$path || !Storage::disk('documentos')->exists($path)) {
                            Notification::make()
                                ->title('Archivo no disponible')
                                ->danger()
                                ->send();

                            return null;
                        }

                        return Storage::disk('documentos')->download(
                            $path,
                            $this->resolveDocumentFilename($record, $path),
                        );
                    })
                    ->visible(fn(Document $record): bool => filled($this->resolveDocumentStoragePath($record))),
                Action::make('toggle_favorite')
                    ->label('')
                    ->tooltip(fn(Document $record): string => $record->favorito ? 'Quitar favorito' : 'Marcar favorito')
                    ->icon(fn(Document $record): string => $record->favorito ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn(Document $record): string => $record->favorito ? 'warning' : 'gray')
                    ->action(function (Document $record): void {
                        $record->update([
                            'favorito' => !(bool)$record->favorito,
                        ]);
                    }),
                \Asmit\AdvancedKanban\Actions\ActionGroup::make([

                    \Asmit\AdvancedKanban\RecordAction\EditAction::make('edit')
                        ->label('')
                        ->model(Document::class)
                        ->modalWidth(Width::FiveExtraLarge)
                        ->schema(function (Schema $schema, Document $record) {
                            $schema = PdfForm::configure($schema);

                            return $schema;
                        })
                        ->action(function (array $data, array $arguments) {

                            if (isset($data['id'])) {
                                $cita = Document::find($data['id']);
                                if ($cita) {
                                    $cita->update($data);
                                } else {
                                    $cita = Document::create($data);
                                }
                                Notification::make()
                                    ->title('Actualizado')
                                    ->success()
                                    ->body('Your bid has successfully been sent')
                                    ->send();
                            }
                        }),

                    \Asmit\AdvancedKanban\RecordAction\DeleteAction::make('delete')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger'),

                ])
                    ->dropdownPlacement('bottom-end'),
            ])
            ->columns(
                $columnas
            );

        return $kanban22;

    }

    public function getTabs(): array
    {

        $todas = Document::query()->count();
        $thisWeek = Document::query()->where('created_at', '>=', now()->subWeek())?->count();
        $thisMonth = Document::query()->where('created_at', '>=', now()->subMonth())?->count();
        $thisYear = Document::query()->where('created_at', '>=', now()->subYear())?->count();

        $cuotas = Document::query()->where('tipodoc', 'cuota')?->count();
        $nominas = Document::query()->where('tipodoc', 'nomina')?->count();
        $repuestos = Document::query()->where('tipodoc', 'repuesto')?->count();
        $agencias = Document::query()->where('tipodoc', 'agencia')?->count();

        return [
            'DOCS.' => Tab::make()
                ->badgeColor('success')
                ->badge($todas),
            'cuota' => Tab::make()
                ->badgeColor($cuotas > 10 ? 'success' : 'warning')
                ->badge($cuotas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipodoc', 'cuota')),
            'nominas' => Tab::make()
                ->badgeColor($nominas > 10 ? 'success' : 'warning')
                ->badge($nominas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipodoc', 'nomina')),
            'repuestos' => Tab::make()
                ->badgeColor($repuestos > 10 ? 'success' : 'warning')
                ->badge($repuestos)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipodoc', 'repuesto')),
            'agencias' => Tab::make()
                ->badgeColor($agencias > 10 ? 'success' : 'warning')
                ->badge($agencias)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('tipodoc', 'agencia')),


        ];
    }

    protected function getViewData(): array
    {
        $tiposdoc = $this->tiposdoc();

        return [
            'tiposdoc' => $tiposdoc,
        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->nombre;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Documentación del taxista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Añadir documento')
                ->hiddenLabel(true)
                ->color('danger')
                ->Button()
                ->icon('heroicon-s-document-plus')
                ->fillForm(fn(): array => [
                    'usuario_id' => $this->getRecord()->id,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['usuario_id'] = $this->getRecord()->id;
                    $data['title'] = $data['title'] ?? ($data['file_name'] ?? ('Documento de ' . $this->getRecord()->nombre));

                    return $data;
                }),

            Action::make('listados')
                ->hiddenLabel(true)
                ->icon('heroicon-o-squares-2x2')
                ->action(fn() => $this->redirect(ManageDocumentosTaxista::getUrl())),
            ActionGroup::make([


                Action::make('agencias_multipagina')
                    ->label('Agencias')
                    ->color('warning')
                    ->hiddenLabel(true)
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Subir Agencias')
                    ->modalCancelActionLabel('Cerrar')
                    ->icon('heroicon-s-squares-plus')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero PDF de Agencias (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarAgenciasMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total documentos");

                                    Notification::make()
                                        ->title('Agencias procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();


                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),


                Action::make('cuotas_multipagina')
                    ->label('Cuotas')
                    ->hiddenLabel(true)
                    ->color('warning')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Subir Cuotas')
                    ->modalCancelActionLabel('Cerrar')
                    ->icon('heroicon-s-squares-plus')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero PDF de cuotas (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarCuotasMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total cuotas");

                                    Notification::make()
                                        ->title('Nóminas procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();


                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),


                Action::make('nominas_multipagina')
                    ->label('Nóminas')
                    ->hiddenLabel(true)
                    ->color('success')
                    ->icon('heroicon-s-document-text')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Nóminas')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Nóminas (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarNominasMultipagina($pdfFile);

                                    $set('notas', "Se han procesado $total nóminas");

                                    Notification::make()
                                        ->title('Nóminas procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),

                Action::make('seguros_multipagina')
                    ->label('Seguros')
                    ->color('info')
                    ->hiddenLabel(true)
                    ->visible(false)
                    ->icon('heroicon-o-shield-check')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Seguros')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Seguros (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->previewable(false)
                            ->reactive()
                            ->storeFileNamesIn('attachment_file_names')
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarSegurosMultipagina($pdfFile);

                                    $set('notas', "Se han procesado $total seguros");

                                    Notification::make()
                                        ->title('Seguros procesados')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),

                Action::make('repuestos_multipagina')
                    ->label('Repuestos')
                    ->color('primary')
                    ->hiddenLabel(true)
                    ->icon('heroicon-o-wrench')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Fact. Repuestos')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Repuestos (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->previewable(false)
                            ->reactive()
                            ->storeFileNamesIn('attachment_file_names')
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarRepuestosMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total fact. repuestos");

                                    Notification::make()
                                        ->title('Fact. Repuestos procesados')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            })
                        ,

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),
            ]),
        ];

    }

    public function form(Form $schema): Form
    {
        return PdfForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                TextColumn::make('file_name')
                    ->label('Archivo')
                    ->description(fn($record) => trim(($record->tipo?->nombre ?? 'Sin tipo') . ' · ' . ($record->departamento?->nombre ?? 'Sin departamento')))
                    ->searchable(),
                TextColumn::make('tipo.nombre')
                    ->label('Tipo')
                    ->badge()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('departamento.nombre')
                    ->label('Departamento')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('favorito')
                    ->label('Favorito')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('view_file')
                    ->label('')
                    ->tooltip('Ver documento')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->url(fn(Document $record): ?string => $this->resolveDocumentViewUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn(Document $record): bool => filled($this->resolveDocumentViewUrl($record))),
                Action::make('download_file')
                    ->label('')
                    ->tooltip('Descargar')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->action(function (Document $record) {
                        $path = $this->resolveDocumentStoragePath($record);

                        if (!$path || !Storage::disk('documentos')->exists($path)) {
                            Notification::make()
                                ->title('Archivo no disponible')
                                ->danger()
                                ->send();

                            return null;
                        }

                        return Storage::disk('documentos')->download(
                            $path,
                            $this->resolveDocumentFilename($record, $path),
                        );
                    })
                    ->visible(fn(Document $record): bool => filled($this->resolveDocumentStoragePath($record))),
                Action::make('toggle_favorite')
                    ->label('')
                    ->tooltip(fn(Document $record): string => $record->favorito ? 'Quitar favorito' : 'Marcar favorito')
                    ->icon(fn(Document $record): string => $record->favorito ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn(Document $record): string => $record->favorito ? 'warning' : 'gray')
                    ->action(function (Document $record): void {
                        $record->update([
                            'favorito' => !(bool)$record->favorito,
                        ]);
                    }),
                EditAction::make()
                    ->mutateDataUsing(function (array $data): array {
                        $data['usuario_id'] = $this->getRecord()->id;

                        return $data;
                    }),
                DeleteAction::make(),
            ])
            ->defaultSort('id', 'desc');
    }

    private function resolveDocumentStoragePath(Document $record): ?string
    {
        $attachment = $this->extractFirstString($record->attachments);
        $fileName = $this->extractFirstString($record->attachment_file_names)
            ?? $this->extractFirstString($record->file_name)
            ?? $this->extractFirstString($record->title);

        if (!filled($attachment)) {
            return filled($fileName) ? ltrim((string)$fileName, '/') : null;
        }

        $attachment = trim((string)$attachment);

        if (Str::startsWith($attachment, ['http://', 'https://'])) {
            $path = parse_url($attachment, PHP_URL_PATH);
            if (is_string($path) && filled($path)) {
                $attachment = $path;
            }
        }

        $attachment = ltrim($attachment, '/');

        if (Str::startsWith($attachment, 'storage/documentos/')) {
            $attachment = Str::after($attachment, 'storage/documentos/');
        } elseif (Str::startsWith($attachment, 'documentos/')) {
            $attachment = Str::after($attachment, 'documentos/');
        }

        if (Str::endsWith($attachment, '/')) {
            return filled($fileName) ? $attachment . $fileName : null;
        }

        return $attachment;
    }

    private function resolveDocumentFilename(Document $record, string $path): string
    {
        return $this->extractFirstString($record->attachment_file_names)
            ?? $this->extractFirstString($record->file_name)
            ?? basename($path)
            ?? ('documento-' . $record->getKey() . '.pdf');
    }

    private function resolveDocumentViewUrl(Document $record): ?string
    {
        $path = $this->resolveDocumentStoragePath($record);

        if (!$path || !Storage::disk('documentos')->exists($path)) {
            return null;
        }

        return Storage::disk('documentos')->url($path);
    }

    private function extractFirstString(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (Str::startsWith($value, ['[', '{'])) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->extractFirstString($decoded);
                }
            }

            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $itemValue = $this->extractFirstString($item);
                if (filled($itemValue)) {
                    return $itemValue;
                }
            }
        }

        return null;
    }
}
