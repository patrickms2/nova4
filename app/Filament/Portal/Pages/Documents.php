<?php

namespace App\Filament\Portal\Pages;

use App\Enums\Icons\PhosphorIcons;
use App\Filament\Admin\Clusters\Taxistas\Pdfs\Schemas\PdfForm;
use App\Filament\Portal\Schemas\Documents\DocumentInfolist;
use App\Models\TaxiCentral\Document;
use App\Models\TaxiCentral\DocumentType;
use App\Models\TaxistaDocument;
use App\Support\Portal\PortalTaxistaContext;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;
use UnitEnum;

class Documents extends Page implements HasTable
{
    use InteractsWithTable;

    protected string $view = 'filament.portal.pages.documents';

    protected static string|BackedEnum|null $navigationIcon = PhosphorIcons::Folders;

    protected static ?string $navigationLabel = 'Documentos';

    protected static string|UnitEnum|null $navigationGroup = 'Mi Portal2';

    protected static ?int $navigationSort = 2;

    protected ?string $heading = 'Documentos';

    protected ?string $subheading = 'Documentación';

    protected array $extraBodyAttributes = [
        'class' => 'portal-documents-screen',
    ];

    public ?int $selectedTipoId = null;

    public bool $showFavoritesRail = false;

    public function table(Table $table): Table
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        return $table
            ->selectable(false)
            ->searchable()
            ->query(
                TaxistaDocument::query()
                    ->when($taxistaId, fn($query) => $query->where('taxista_user_id', $taxistaId), fn($query) => $query->whereRaw('1 = 0'))
                    ->when($this->selectedTipoId, fn($query) => $query->where('document_type', $this->selectedTipoId))
                    ->orderByDesc('is_favorite')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
            )
            ->columns([
                TextColumn::make('id')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('file_path')
                    ->label('Archivo')
                    ->html()
                    ->state(function ($record): HtmlString {
                        $name = e((string)($record->title ?: 'Sin archivo'));
                        $tipo = e($this->resolveTypeLabel($record));
                        $reference = e((string)($record->referencia ?: $record->reference ?: 'Sin referencia'));
                        $date = e((string)($record->created_at?->format('d/m/Y H:i') ?? '-'));
                        $tileStyle = e($this->resolveTileStyle($record));

                        return new HtmlString(
                            "<div class='portal-row-tile' style='{$tileStyle}'>
                                <div class='portal-row-tile__head'>
                                    <p class='portal-row-tile__title'>{$name}</p>
                                </div>
                                <p class='portal-row-tile__meta'>{$date}</p>
                                <p class='portal-row-tile__meta portal-row-tile__meta--split'>
                                    <span class='portal-row-tile__badge portal-badge-info'>{$tipo}</span>
                                    <span class='portal-row-tile__badge portal-badge-gray'>{$reference}</span>
                                </p>
                            </div>"
                        );
                    })
                    ->searchable(['file_path', 'title', 'reference', 'referencia'])
                    ->extraAttributes(['class' => 'portal-row-tile-cell']),

                TextColumn::make('documentType.name')
                    ->label('Tipo')
                    ->badge()
                    ->visible(false)
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                /*TextColumn::make('departamento.nombre')
                    ->label('Departamento')
                    ->badge()
                    ->color('gray')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),*/
                IconColumn::make('is_favorite')
                    ->label('Favorito')
                    ->boolean()
                    ->trueColor('warning')
                    ->falseColor('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([
                /*SelectFilter::make('document_type_id')
                    ->label('Tipo')
                    ->relationship('documentType', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn() => $this->selectedTipoId === null),
                SelectFilter::make('departamento_id')
                    ->label('Departamento')
                    ->relationship('departamento', 'nombre')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('favorito')
                    ->label('Favorito'),*/
            ])
            ->recordActions([
                Action::make('toggle_favorite')
                    ->label('')
                    ->iconSize('xl')
                    ->button()
                    ->tooltip(fn(TaxistaDocument $record): string => $record->favorito ? 'Quitar favorito' : 'Marcar favorito')
                    ->icon(fn(TaxistaDocument $record): string => $record->favorito ? 'heroicon-s-star' : 'heroicon-o-star')
                    ->color(fn(TaxistaDocument $record): string => $record->favorito ? 'warning' : 'gray')
                    ->extraAttributes(['x-on:click.stop' => ''])
                    ->action(function (TaxistaDocument $record): void {
                        $markAsFavorite = !(bool)$record->favorito;

                        $record->update([
                            'favorito' => $markAsFavorite,
                        ]);

                        Notification::make()
                            ->title($markAsFavorite ? 'Documento favorito' : 'Favorito eliminado')
                            ->body($markAsFavorite ? 'Se guardó en favoritos.' : 'Se quitó de favoritos.')
                            ->success()
                            ->send();
                    }),
                Action::make('preview')
                    ->label('')
                    ->tooltip('Ver fichero')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->iconSize('xl')
                    ->url(fn(TaxistaDocument $record): ?string => $this->resolvePreviewUrl($record), shouldOpenInNewTab: true)
                    ->extraAttributes(['x-on:click.stop' => ''])
                    ->visible(fn(TaxistaDocument $record): bool => filled($this->resolvePreviewUrl($record))),
                Action::make('download')
                    ->label('')
                    ->tooltip('Descargar')
                    ->iconSize('xl')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('gray')
                    ->extraAttributes(['x-on:click.stop' => ''])
                    ->action(function (TaxistaDocument $record) {
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
                    ->visible(fn(TaxistaDocument $record): bool => filled($this->resolveDownloadPath($record))),
                ActionGroup::make([
                    ViewAction::make()
                        ->label('Ver')
                        ->slideOver()
                        ->iconSize('xl')
                        ->modalWidth(Width::Large)
                        ->extraModalWindowAttributes(['class' => 'portal-detail-modal'])
                        ->modalIcon(Heroicon::DocumentText)
                        ->modalHeading(fn($record): string => (string)($record->file_name ?: $record->title ?: 'Detalle de documento'))
                        ->modalDescription(fn($record): string => (string)($record->created_at?->format('d/m/Y H:i') ?: 'Sin fecha'))
                        ->stickyModalHeader()
                        ->stickyModalFooter()
                        ->schema(fn(Schema $schema) => DocumentInfolist::configure($schema))
                        ->extraModalFooterActions(function ($record, $livewire): array {
                            return [
                                Action::make('edit_from_view')
                                    ->label('Editar')
                                    ->icon(Heroicon::PencilSquare)
                                    ->color('primary')
                                    ->button()
                                    ->action(fn() => $livewire->replaceMountedTableAction('edit', (string)$record->getKey())),
                            ];
                        }),
                    EditAction::make()
                        ->slideOver()
                        ->successNotificationTitle('Documento actualizado')
                        ->schema(fn(Schema $schema) => PdfForm::configure($schema))
                        ->mutateDataUsing(function (array $data): array {
                            $data['usuario_id'] = PortalTaxistaContext::taxistaId();

                            return $data;
                        }),
                    DeleteAction::make()
                        ->successNotificationTitle('Documento eliminado'),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->extraAttributes(['x-on:click.stop' => '']),
            ])
            ->recordAction('view')
            ->toolbarActions([
                Action::make('go_dashboard')
                    ->label('')
                    ->tooltip('Volver a Documentos')
                    ->icon('heroicon-o-home')
                    ->visible(false)
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'portal-home-action toolbar-segment-pre'])
                    ->url(Dashboard::getUrl()),
                Action::make('back_to_folders')
                    ->label('Carpetas')
                    ->hiddenLabel()
                    ->tooltip('Volver a carpetas')
                    ->icon('heroicon-o-folder-open')
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'toolbar-segment-pre'])
                    ->visible(fn(): bool => $this->selectedTipoId !== null)
                    ->action(fn() => $this->backToFolders()),
                Action::make('view_kanban')
                    ->label('Kanban')
                    ->hiddenLabel()
                    ->tooltip('Kanban')
                    ->icon(PhosphorIcons::KanbanDuotone)
                    ->color('gray')
                    ->outlined()
                    ->extraAttributes(['class' => 'toolbar-segment-pre portal-view-switch'])
                    ->visible(fn(): bool => $this->selectedTipoId === null)
                    ->url(DocumentsKanban::getUrl()),
                ActionGroup::make([
                    Action::make('sort_recent')
                        ->label('Más recientes')
                        ->icon('heroicon-o-arrow-trending-down')
                        ->action(fn() => $this->sortTable('created_at', 'desc')),
                    Action::make('sort_oldest')
                        ->label('Más antiguas')
                        ->icon('heroicon-o-arrow-trending-up')
                        ->action(fn() => $this->sortTable('created_at', 'asc')),
                ])
                    ->label('Ordenar')
                    ->hiddenLabel()
                    ->tooltip('Ordenar')
                    ->icon('heroicon-o-arrows-up-down')
                    ->color('gray')
                    ->outlined()
                    ->visible(fn(): bool => $this->selectedTipoId !== null)
                    ->extraAttributes(['class' => 'toolbar-segment-post']),
                CreateAction::make('add_document')
                    ->label('Añadir')
                    ->hiddenLabel()
                    ->tooltip('Crear documento')
                    ->icon('heroicon-o-plus')
                    ->color('danger')
                    ->extraAttributes(['class' => 'toolbar-segment-final portal-action-add'])
                    ->successNotificationTitle('Documento creado')
                    ->model(TaxistaDocument::class)
                    ->schema(fn(Schema $schema) => PdfForm::configure($schema))
                    ->fillForm(function (): array {
                        return [
                            'usuario_id' => PortalTaxistaContext::taxistaId(),
                            'document_type_id' => $this->selectedTipoId,
                        ];
                    })
                    ->mutateDataUsing(function (array $data): array {
                        $taxistaId = PortalTaxistaContext::taxistaId();
                        $data['usuario_id'] = $taxistaId;
                        if ($this->selectedTipoId) {
                            $data['document_type_id'] = $this->selectedTipoId;
                        }
                        $data['title'] = $data['title'] ?? ($data['file_name'] ?? ('Documento de taxista #' . $taxistaId));

                        return $data;
                    })
                    ->visible(fn(): bool => $this->selectedTipoId !== null),
            ])
            ->emptyStateActions([]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('header_view_kanban')
                ->label('Kanban')
                ->hiddenLabel()
                ->tooltip('Vista Kanban')
                ->icon(PhosphorIcons::KanbanDuotone)
                ->color('gray')
                ->outlined()
                ->visible(fn(): bool => $this->selectedTipoId === null)
                ->url(DocumentsKanban::getUrl()),
            Action::make('header_add_document')
                ->label('Añadir')
                ->hiddenLabel()
                ->tooltip('Añadir documento')
                ->icon('heroicon-o-plus')
                ->color('danger')
                ->visible(fn(): bool => $this->selectedTipoId === null)
                ->action(fn() => $this->mountTableAction('add_document')),
        ];
    }

    public function getHeading(): string
    {
        return (string)$this->heading;
    }

    public function getSubheading(): ?string
    {
        return $this->subheading;
    }

    public function getBreadcrumbs(): array
    {
        if ($this->selectedFolder) {
            return [
                static::getUrl() => 'Documentos',
                '#' => (string)($this->selectedFolder['name'] ?? 'Tipo'),
            ];
        }

        return [
            static::getUrl() => 'Documentos',
        ];
    }

    public function getFolderTypesProperty(): Collection
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        if (!$taxistaId) {
            return collect();
        }

        $counts = TaxistaDocument::query()
            ->where('usuario_id', $taxistaId)
            ->whereNotNull('document_type_id')
            ->selectRaw('document_type_id, COUNT(*) as documents_count')
            ->groupBy('document_type_id')
            ->pluck('documents_count', 'document_type_id');

        if ($counts->isEmpty()) {
            return collect();
        }

        return DocumentType::query()
            ->whereIn('id', $counts->keys())
            ->orderBy('order')
            ->orderBy('name')
            ->get(['id', 'name', 'color', 'icon'])
            ->map(function (DocumentType $tipo) use ($counts): array {
                return [
                    'id' => (int)$tipo->id,
                    'name' => (string)$tipo->name,
                    'icon' => (string)($tipo->icon ?: 'heroicon-o-folder'),
                    'color' => (string)($tipo->color ?: ''),
                    'count' => (int)($counts[(int)$tipo->id] ?? 0),
                ];
            })
            ->values();
    }

    public function getDocumentsSummaryProperty(): array
    {
        $folders = $this->folderTypes;

        return [
            'total' => (int)$folders->sum('count'),
            'folders' => (int)$folders->count(),
            'favorites' => (int)$this->favoriteDocuments->count(),
        ];
    }

    public function getSelectedFolderProperty(): ?array
    {
        if (!$this->selectedTipoId) {
            return null;
        }

        return $this->folderTypes
            ->first(fn(array $folder): bool => $folder['id'] === $this->selectedTipoId);
    }

    public function getIsFolderViewProperty(): bool
    {
        return $this->selectedTipoId === null;
    }

    public function openFolder(int $tipoId): void
    {
        $exists = $this->folderTypes
            ->contains(fn(array $folder): bool => $folder['id'] === $tipoId);

        if (!$exists) {
            return;
        }

        $this->selectedTipoId = $tipoId;
        $this->showFavoritesRail = false;
        $this->resetTable();
    }

    public function backToFolders(): void
    {
        $this->selectedTipoId = null;
        $this->showFavoritesRail = false;
        $this->resetTable();
    }

    public function openFavoritesRail(): void
    {
        $this->showFavoritesRail = true;
    }

    public function closeFavoritesRail(): void
    {
        $this->showFavoritesRail = false;
    }

    public function openDocument(int $documentId): void
    {
        $this->mountTableAction('view', (string)$documentId);
    }

    public function getFavoriteDocumentsProperty(): Collection
    {
        $taxistaId = PortalTaxistaContext::taxistaId();

        if (!$taxistaId) {
            return collect();
        }

        return Document::query()
            ->where('usuario_id', $taxistaId)
            ->where('favorito', true)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(12)
            ->get(['id', 'title', 'attachment_file_names', 'reference', 'referencia', 'document_type_id', 'created_at'])
            ->map(function (TaxistaDocument $document): array {
                $name = trim((string)($document->title ?: 'Sin título'));
                if ($name === '') {
                    $name = 'Sin título';
                }

                return [
                    'id' => (int)$document->id,
                    'title' => $name,
                    'date' => (string)($document->created_at?->format('d/m/Y H:i') ?? '-'),
                    'type' => $this->resolveTypeLabel($document),
                    'reference' => (string)($document->referencia ?: $document->reference ?: 'Sin referencia'),
                ];
            })
            ->values();
    }

    private function resolveTypeLabel(TaxistaDocument $record): string
    {
        $tipoByRelation = $record->document_type_id
            ? (string)($record->documentType()->value('name') ?? '')
            : '';

        if (filled($tipoByRelation)) {
            return $tipoByRelation;
        }

        $tipodoc = trim((string)($record->tipodoc ?? ''));
        if ($tipodoc !== '') {
            return mb_strtoupper($tipodoc);
        }

        $tipoRaw = trim((string)($record->getAttribute('tipo') ?? ''));
        if ($tipoRaw !== '') {
            return $tipoRaw;
        }

        return 'Sin tipo';
    }

    private function resolveDownloadPath(TaxistaDocument $record): ?string
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

    private function resolveDownloadFilename(TaxistaDocument $record, string $path): string
    {
        return $this->extractFirstString($record->attachment_file_names)
            ?? $this->extractFirstString($record->file_name)
            ?? basename($path)
            ?? ('documento-' . $record->getKey() . '.pdf');
    }

    private function resolveTileStyle(TaxistaDocument $record): string
    {
        $hex = $this->resolveTypeColorHex($record);

        if (!$hex) {
            return '';
        }

        $bg = $this->hexToRgba($hex, 0.12);
        $border = $this->hexToRgba($hex, 0.34);

        return "background: linear-gradient(180deg, {$bg} 0%, #ffffff 100%); border-color: {$border};";
    }

    private function resolveTypeColorHex(TaxistaDocument $record): ?string
    {
        $raw = (string)($record->documentType?->color ?? $record->tipo?->color ?? '');
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (!Str::startsWith($raw, '#')) {
            $raw = '#' . $raw;
        }

        if (!preg_match('/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $raw)) {
            return null;
        }

        if (strlen($raw) === 4) {
            $raw = '#' . $raw[1] . $raw[1] . $raw[2] . $raw[2] . $raw[3] . $raw[3];
        }

        return strtoupper($raw);
    }

    private function hexToRgba(string $hex, float $alpha): string
    {
        $hex = ltrim($hex, '#');

        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        return "rgba({$r}, {$g}, {$b}, {$alpha})";
    }

    private function resolvePreviewUrl(TaxistaDocument $record): ?string
    {
        $attachment = $this->extractFirstString($record->attachments);
        if (filled($attachment) && Str::startsWith((string)$attachment, ['http://', 'https://'])) {
            return (string)$attachment;
        }

        $path = $this->resolveDownloadPath($record);
        if (!filled($path) || !Storage::disk('documentos')->exists($path)) {
            return null;
        }

        try {
            return Storage::disk('documentos')->url($path);
        } catch (Throwable) {
            return null;
        }
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
