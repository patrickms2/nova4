<?php

declare(strict_types=1);

namespace App\Filament\App\Pages;

use App\Filament\App\Resources\TaxistaDocuments\Pages\KanbanView;
use App\Filament\App\Resources\TaxistaDocuments\Pages\ListTaxistaDocuments;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Services\Taxistas\TaxistaImportIssueBoard;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Pages\Page;
use Livewire\Attributes\Url;

class TaxistaImportIssues extends Page
{
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-exclamation-triangle';

    protected static ?string $navigationLabel = 'Alertas Importacion';

    protected static string|null|\UnitEnum $navigationGroup = 'Servicios de Taxista';

    protected static ?int $navigationSort = 8;

    protected static ?string $slug = 'taxista-import-issues';

    protected string $view = 'filament.app.pages.taxista-import-issues';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'type')]
    public string $documentType = 'all';

    /**
     * @var array{
     *     generated_at: string,
     *     total: int,
     *     by_document_type: array<string, int>,
     *     columns: array<int, array{
     *         key: string,
     *         label: string,
     *         color: string,
     *         description: string,
     *         count: int,
     *         items: array<int, array{
     *             key: string,
     *             type: string,
     *             label: string,
     *             source: string,
     *             source_name: string,
     *             document_type: string,
     *             nif: string|null,
     *             page: int|null,
     *             user_id: int|null,
     *             user_name: string|null,
     *             role: string|null,
     *             message: string,
     *             happened_at: string,
     *             metadata: array<string, mixed>,
     *         }>
     *     }>
     * }
     */
    public array $boardData = [
        'generated_at' => '',
        'total' => 0,
        'by_document_type' => [],
        'columns' => [],
    ];

    /** @var array<string, string> */
    public array $documentTypeOptionsData = ['all' => 'Todos'];

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }

    public function getTitle(): string
    {
        return 'Alertas de Importacion';
    }

    public function getSubheading(): ?string
    {
        return 'Incidencias activas detectadas en importaciones multipagina. Al corregir datos y recargar, desaparecen.';
    }

    public function mount(): void
    {
        $this->refreshBoard();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('documents')
                ->label('Documentos')
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => ListTaxistaDocuments::getUrl(tenant: Filament::getTenant())),
            Action::make('refresh')
                ->label('Refrescar')
                ->icon('heroicon-o-arrow-path')
                ->action(function (): void {
                    $this->refreshBoard();
                }),
            Action::make('kanban')
                ->label('Kanban documentos')
                ->icon('heroicon-o-rectangle-group')
                ->url(fn (): string => KanbanView::getUrl(panel: 'app', tenant: Filament::getTenant())),
            Action::make('taxistas')
                ->label('Taxistas')
                ->icon('heroicon-o-users')
                ->url(fn (): string => TaxistaResource::getUrl(tenant: Filament::getTenant())),
        ];
    }

    public function updatedSearch(): void
    {
        $this->refreshBoard();
    }

    public function updatedDocumentType(): void
    {
        $this->refreshBoard();
    }

    protected function refreshBoard(): void
    {
        $this->boardData = app(TaxistaImportIssueBoard::class)->build(
            search: $this->search,
            documentType: $this->documentType,
        );

        $options = ['all' => 'Todos'];

        foreach (array_keys($this->boardData['by_document_type']) as $documentType) {
            $options[$documentType] = str($documentType)->replace('_', ' ')->title()->toString();
        }

        $this->documentTypeOptionsData = $options;
    }

    public function getColorDotClass(string $color): string
    {
        return match ($color) {
            'rose' => 'bg-rose-500',
            'amber' => 'bg-amber-500',
            'sky' => 'bg-sky-500',
            'violet' => 'bg-violet-500',
            default => 'bg-slate-500',
        };
    }
}
