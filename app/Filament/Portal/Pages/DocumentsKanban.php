<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\Admin\Clusters\Taxistas\Pdfs\Schemas\PdfForm;
use App\Models\Taxi\Departamento;
use App\Models\TaxiCentral\Document;
use App\Models\TaxiCentral\DocumentType;
use App\Models\TaxistaDocument;
use App\Support\Portal\PortalTaxistaContext;
use Asmit\AdvancedKanban\Actions\ActionGroup;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Kanban;
use Asmit\AdvancedKanban\Pages\KanbanPage;
use Asmit\AdvancedKanban\RecordAction\DeleteAction;
use Asmit\AdvancedKanban\RecordAction\EditAction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentsKanban extends KanbanPage
{
    use HasTabs;

    /** @var array<int, string> */
    public array $hiddenTipoIds = [];

    protected static bool $shouldRegisterNavigation = false;

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::KanbanDuotone;

    protected static string $model = TaxistaDocument::class;

    protected static string $recordTitleAttribute = 'file_path';

    protected static string $recordStatusAttribute = 'document_type';

    protected static string $columnHeaderComponent = 'advanced-kanban::column-header_doc';

    protected static string $cardComponent = 'portal.documents-kanban-card';

    protected static ?string $title = 'Mis Documentos';

    public function mount(): void
    {
        $this->hiddenTipoIds = array_map('strval',
            session()->get($this->hiddenTiposSessionKey(), [])
        );
    }

    private function hiddenTiposSessionKey(): string
    {
        $taxistaId = PortalTaxistaContext::taxistaId() ?? 'guest';

        return 'portal.kanban.documents.hidden_tipos.' . $taxistaId;
    }

    public function handleRecordMove(string $newStatus, Model $record): void
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        if (!$taxistaId || (int)$record->usuario_id !== (int)$taxistaId) {
            return;
        }

        $tipoId = (int)$newStatus;
        $tipoName = (string)(DocumentType::query()->whereKey($tipoId)->value('name') ?? '');

        if ($tipoName === '') {
            return;
        }

        $record->update(['document_type_id' => $tipoId]);

        Notification::make()
            ->title('Documento movido')
            ->body('Ahora está en "' . $tipoName . '".')
            ->success()
            ->send();
    }

    public function kanban(Kanban $kanban): Kanban
    {
        $taxistaId = PortalTaxistaContext::taxistaId();
        $tipos = DocumentType::query()
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'icon']);

        $columns = $tipos->map(function (DocumentType $tipo) use ($taxistaId) {
            if (in_array((string)$tipo->id, $this->hiddenTipoIds, true)) {
                return null;
            }

            return KanbanColumn::make((string)$tipo->id)
                ->label($tipo->name)
                ->description('Documentos de ' . $tipo->name)
                ->icon($tipo->icon ?: 'heroicon-o-document')
                ->iconcolor($tipo->color ?: 'gray')
                ->modifyRecordQueryUsing(function ($query) use ($taxistaId, $tipo) {
                    return $query
                        ->where('usuario_id', $taxistaId ?: 0)
                        ->where('document_type_id', $tipo->id)
                        ->orderByDesc('favorito')
                        ->orderByDesc('created_at')
                        ->orderByDesc('id');
                });
        })->filter()->values()->all();

        return $kanban
            ->model(Document::class)
            ->statusField('document_type_id')
            ->titleField('file_name')
            ->descriptionField('observaciones')
            ->searchableFields(['file_name', 'title', 'referencia', 'observaciones'])
            ->enableLoadingIndicator()
            ->enableFilterIndicator()
            ->recordsPerColumn(20)
            ->columnHeaderActions([
                \Asmit\AdvancedKanban\Actions\CreateAction::make('new')
                    ->schema(function (Schema $schema, array $arguments) {
                        $defaultTipoId = (int)($arguments['status'] ?? 0);

                        return PdfForm::configure($schema, $defaultTipoId > 0 ? $defaultTipoId : null);
                    })
                    ->mountUsing(function (array $arguments, Schema $form) {
                        $defaultTipoId = (int)($arguments['status'] ?? 0);

                        return $form->fill([
                            'document_type_id' => $defaultTipoId > 0 ? $defaultTipoId : null,
                        ]);
                    })
                    ->action(function (array $arguments, array $data) use ($taxistaId): void {
                        if (!$taxistaId) {
                            return;
                        }

                        $data['usuario_id'] = $taxistaId;
                        $data['document_type_id'] = (int)($arguments['status'] ?? ($data['document_type_id'] ?? 0));
                        $data['title'] = $data['title'] ?? ($data['file_name'] ?? ('Documento de taxista #' . $taxistaId));

                        Document::create($data);
                    })
                    ->icon('heroicon-o-plus')
                    ->hiddenLabel()
                    ->link(),
            ])
            ->filterFormSchema([
                Select::make('document_type_id')
                    ->label('Tipo')
                    ->placeholder(__('filament-forms::components.select.placeholder'))
                    ->options(fn() => DocumentType::query()->where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Select::make('departamento_id')
                    ->label('Departamento')
                    ->placeholder(__('filament-forms::components.select.placeholder'))
                    ->options(fn() => Departamento::query()->orderBy('nombre')->pluck('nombre', 'id')->toArray())
                    ->multiple()
                    ->searchable()
                    ->preload(),
                Select::make('favorito')
                    ->label('Favorito')
                    ->placeholder(__('filament-forms::components.select.placeholder'))
                    ->options([
                        '1' => 'Solo favoritos',
                        '0' => 'No favoritos',
                    ]),
            ])
            ->applyFiltersUsing(function (Builder $query, array $filters) use ($taxistaId): Builder {
                $query->where('usuario_id', $taxistaId ?: 0);

                if (!empty($filters['document_type_id'])) {
                    $query->whereIn('document_type_id', $filters['document_type_id']);
                }

                if (!empty($filters['departamento_id'])) {
                    $query->whereIn('departamento_id', $filters['departamento_id']);
                }

                if (array_key_exists('favorito', $filters) && $filters['favorito'] !== null && $filters['favorito'] !== '') {
                    $query->where('favorito', (int)$filters['favorito']);
                }

                return $query;
            })
            ->recordActions([
                Action::make('download')
                    ->label('')
                    ->tooltip('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->action(function (Document $record) {
                        $path = $this->resolveDownloadPath($record);

                        if (!$path || !Storage::disk('documentos')->exists($path)) {
                            Notification::make()
                                ->title('Archivo no disponible')
                                ->danger()
                                ->send();

                            return null;
                        }

                        Notification::make()
                            ->title('Descarga iniciada')
                            ->success()
                            ->send();

                        return Storage::disk('documentos')->download(
                            $path,
                            $this->resolveDownloadFilename($record, $path),
                        );
                    })
                    ->visible(fn(Document $record): bool => filled($this->resolveDownloadPath($record))),
                ActionGroup::make([
                    EditAction::make('edit')
                        ->label('')
                        ->model(Document::class)
                        ->modalWidth(Width::FiveExtraLarge)
                        ->schema(function (Schema $schema) {
                            return PdfForm::configure($schema);
                        })
                        ->action(function (array $data): void {
                            if (!isset($data['id'])) {
                                return;
                            }

                            $taxistaId = PortalTaxistaContext::taxistaId();
                            $doc = Document::query()
                                ->whereKey($data['id'])
                                ->where('usuario_id', $taxistaId ?: 0)
                                ->first();

                            if (!$doc) {
                                return;
                            }

                            $data['usuario_id'] = $taxistaId;
                            $doc->update($data);
                        }),
                    DeleteAction::make('delete')
                        ->label('')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger'),
                ])->dropdownPlacement('bottom-end'),
            ])
            ->columns($columns);
    }

    public function getTabs(): array
    {
        $taxistaId = PortalTaxistaContext::taxistaId();
        $counts = Cache::remember(
            sprintf('portal:documents-kanban:tabs:%s', $taxistaId ?: 'guest'),
            now()->addSeconds(20),
            static function () use ($taxistaId): array {
                $baseQuery = Document::query()->where('usuario_id', $taxistaId ?: 0);

                return [
                    'all' => (clone $baseQuery)->count(),
                    'favorites' => (clone $baseQuery)->where('favorito', 1)->count(),
                    'recent' => (clone $baseQuery)->where('created_at', '>=', now()->subDays(30))->count(),
                ];
            },
        );

        return [
            'Todos' => Tab::make()->badge($counts['all']),
            'Favoritos' => Tab::make()
                ->badge($counts['favorites'])
                ->modifyQueryUsing(fn(Builder $query) => $query->where('favorito', 1)),
            'Recientes' => Tab::make()
                ->badge($counts['recent'])
                ->modifyQueryUsing(fn(Builder $query) => $query->where('created_at', '>=', now()->subDays(30))),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('go_dashboard')
                ->label('Inicio')
                ->hiddenLabel()
                ->tooltip('Inicio')
                ->icon('heroicon-o-home')
                ->color('gray')
                ->outlined()
                ->url(Dashboard::getUrl()),
            Action::make('table')
                ->label('Tabla')
                ->hiddenLabel()
                ->tooltip('Tabla')
                ->icon('heroicon-o-table-cells')
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'toolbar-segment-pre portal-view-switch'])
                ->url(Documents::getUrl()),
            CreateAction::make('add_document')
                ->label('Añadir')
                ->hiddenLabel()
                ->tooltip('Crear documento')
                ->extraAttributes(['class' => 'toolbar-segment-final portal-action-add'])
                ->successNotificationTitle('Documento creado')
                ->model(Document::class)
                ->schema(fn(Schema $schema) => PdfForm::configure($schema))
                ->fillForm(function (): array {
                    return [
                        'usuario_id' => PortalTaxistaContext::taxistaId(),
                    ];
                })
                ->mutateDataUsing(function (array $data): array {
                    $taxistaId = PortalTaxistaContext::taxistaId();
                    $data['usuario_id'] = $taxistaId;
                    $data['title'] = $data['title'] ?? ($data['file_name'] ?? ('Documento de taxista #' . $taxistaId));

                    return $data;
                }),
            Action::make('hide_columns')
                ->label('Ocultar')
                ->hiddenLabel()
                ->tooltip('Ocultar columnas')
                ->icon('heroicon-o-eye-slash')
                ->color('gray')
                ->outlined()
                ->extraAttributes(['class' => 'toolbar-segment-post portal-columns-toggle'])
                ->fillForm(fn(): array => [
                    'hidden_tipo_ids' => $this->hiddenTipoIds,
                ])
                ->schema([
                    ToggleButtons::make('hidden_tipo_ids')
                        ->label('Tipos a ocultar')
                        ->multiple()
                        ->inline()
                        ->options(fn() => DocumentType::query()
                            ->where('is_active', 1)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->mapWithKeys(fn($name, $id) => [(string)$id => $name])
                            ->toArray()),
                ])
                ->action(function (array $data): void {
                    $this->hiddenTipoIds = array_map('strval', $data['hidden_tipo_ids'] ?? []);
                    session()->put($this->hiddenTiposSessionKey(), $this->hiddenTipoIds);

                    // Rebuild Kanban object to recalculate column visibility.
                    $this->kanban = null;
                    $this->updateCachedKanbanTransitions();
                    $this->loadKanbanRecords();

                    Notification::make()
                        ->title('Columnas actualizadas')
                        ->body('Se ocultaron ' . count($this->hiddenTipoIds) . ' tipo(s) en Kanban.')
                        ->success()
                        ->send();
                }),
        ];
    }

    public function toggleFavorite(int|string $recordId): void
    {
        $taxistaId = PortalTaxistaContext::taxistaId();
        if (!$taxistaId) {
            return;
        }

        $record = Document::query()
            ->whereKey($recordId)
            ->where('usuario_id', $taxistaId)
            ->first();

        if (!$record) {
            return;
        }

        $record->update([
            'favorito' => !(bool)$record->favorito,
        ]);

        $this->loadKanbanRecords();

        Notification::make()
            ->title($record->favorito ? 'Documento favorito' : 'Favorito eliminado')
            ->body($record->favorito ? 'Se guardó en favoritos.' : 'Se quitó de favoritos.')
            ->success()
            ->send();
    }

    private function resolveDownloadPath(Document $record): ?string
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

    private function resolveDownloadFilename(Document $record, string $path): string
    {
        return $this->extractFirstString($record->attachment_file_names)
            ?? $this->extractFirstString($record->file_name)
            ?? basename($path)
            ?? ('documento-' . $record->getKey() . '.pdf');
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
