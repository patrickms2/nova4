<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Support\TaxistaDocumentTypes;
use App\Models\TaxistaDocument;
use App\Models\TaxiCentral\DocumentType;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use Asmit\AdvancedKanban\Actions\ActionGroup;
use Asmit\AdvancedKanban\Actions\CreateAction;
use Asmit\AdvancedKanban\Columns\KanbanColumn;
use Asmit\AdvancedKanban\Kanban;
use Asmit\AdvancedKanban\Pages\KanbanPage;
use Asmit\AdvancedKanban\RecordAction\DeleteAction;
use Asmit\AdvancedKanban\RecordAction\EditAction;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Resources\Pages\PageRegistration;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

// Import necesario
use Filament\Resources\Concerns\HasTabs;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\SpatieMediaLibraryImageEntry;
use Filament\Infolists\Components\SpatieTagsEntry;
use Filament\Infolists\Components\TextEntry;

final class KanbanView extends KanbanPage
{
    use HasTabs;

    public $tiposdoc;
    public ?array $data = [];

    protected static ?string $model = TaxistaDocument::class;

    protected static ?string $resource = null;
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $statusEnum = TaxistaDocumentTypes::class;

    protected static string $recordTitleAttribute = 'title';

    protected static string $recordStatusAttribute = 'document_type';

    protected static string $columnHeaderComponent = 'advanced-kanban::column-header_doc';

    protected static string $cardComponent = 'advanced-kanban::card_doc';

    public static function route(string $path): PageRegistration
    {
        return new PageRegistration(
            page: static::class,
            route: fn(Panel $panel): Route => RouteFacade::get($path, static::class)
                ->middleware(static::getRouteMiddleware($panel))
                ->withoutMiddleware(static::getWithoutRouteMiddleware($panel)),
        );
    }

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
                    TextInput::make('title')
                        ->label('Título del documento')
                        ->required(),
                    Select::make('document_type')
                        ->label('Tipo de documento')
                        ->options(TaxistaDocumentTypes::options())
                        ->required()
                        ->default($arguments['status'] ?? 'otros'),
                    Select::make('taxista_user_id')
                        ->label('Taxista')
                        ->relationship('taxista', 'name')
                        ->searchable()
                        ->preload(),
                ];
            })
            ->action(function (array $data) {
                $data['uploaded_by_user_id'] = auth()->id();
                $data['status'] = 'activo';
                $data['uploaded_at'] = now();

                TaxistaDocument::create($data);

                Notification::make()
                    ->title('Documento creado')
                    ->success()
                    ->send();
            });
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $data['uploaded_by_user_id'] = auth()->id();
        $data['status'] = 'activo';
        $data['uploaded_at'] = now();

        $documento = TaxistaDocument::create($data);

        $this->form->model($documento)->saveRelationships();
        $this->form->fill();

        Notification::make()
            ->title('Documento creado')
            ->success()
            ->send();
    }

    #[Computed]
    public function tiposdoc()
    {
        return TaxistaDocument::query()
            ->select('document_type', \DB::raw('count(*) as count'))
            ->groupBy('document_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($item) {
                if (!TaxistaDocumentTypes::hasSlug($item->document_type)) {
                    return null;
                }
                $color = TaxistaDocumentTypes::getColorBySlug($item->document_type);
                $icon = TaxistaDocumentTypes::getIconBySlug($item->document_type);
                $tipos = TaxistaDocumentTypes::options();
                return [
                    'slug' => $item->document_type,
                    'id' => $item->document_type,
                    'nombre' => $tipos[$item->document_type] ?? $item->document_type,
                    'count' => $item->count,
                    'color' => $color,
                    'icono' => $icon
                ];
            });
    }

    public function getColorByType(string $type): string
    {
        $colors = [
            'nomina' => '#10b981',
            'cuotas' => '#f59e0b',
            'agencias' => '#3b82f6',
            'repuestos' => '#8b5cf6',
            'seguro' => '#ef4444',
            'impuesto' => '#6366f1',
            'certificado' => '#14b8a6',
            'otros' => '#6b7280'
        ];
        return $colors[$type] ?? '#6b7280';
    }

    public function getIconByType(string $type): string
    {
        $icons = [
            'nomina' => 'heroicon-o-banknotes',
            'cuotas' => 'heroicon-o-credit-card',
            'agencias' => 'heroicon-o-building-office',
            'repuestos' => 'heroicon-o-wrench-screwdriver',
            'seguro' => 'heroicon-o-shield-check',
            'impuesto' => 'heroicon-o-document-text',
            'certificado' => 'heroicon-o-award',
            'otros' => 'heroicon-o-folder'
        ];
        return $icons[$type] ?? 'heroicon-o-folder';
    }

    public function kanban(Kanban $kanban): Kanban
    {
        $tipos = TaxistaDocumentTypes::options();
        $columnas = [];

        foreach ($tipos as $slug => $label) {
            $columnas[] = KanbanColumn::make($slug)
                ->hidden(false)
                ->label($label)
                ->description('Gestión de ' . $label)
                ->modifyRecordQueryUsing(function ($query) use ($slug) {
                    return $query->where('document_type', $slug)->orderBy('created_at', 'desc');
                });
        }

        return $kanban
            ->model(TaxistaDocument::class)
            ->statusField('document_type')
            ->titleField('title')
            ->descriptionField('notas')
            ->searchableFields(['title', 'document_type', 'notas'])
            ->enableLoadingIndicator()
            ->enableFilterIndicator()
            ->columnHeaderActions([
                CreateAction::make('nueva')
                    ->schema(function (Schema $schema, array $arguments) {
                        return [
                            TextInput::make('title')
                                ->label('Título del documento')
                                ->required(),
                            Select::make('document_type')
                                ->label('Tipo de documento')
                                ->options(TaxistaDocumentTypes::options())
                                ->required()
                                ->default($arguments['status'] ?? 'otros'),
                            Select::make('taxista_user_id')
                                ->label('Taxista')
                                ->relationship('taxista', 'name')
                                ->searchable()
                                ->preload()
                                ->required(),
                            TextInput::make('notas')
                                ->label('Notas')
                                ->maxLength(500),
                        ];
                    })
                    ->action(function (array $data, array $arguments) {
                        $data['uploaded_by_user_id'] = auth()->id();
                        $data['status'] = 'activo';
                        $data['uploaded_at'] = now();
                        $data['document_type'] = $arguments['status'] ?? 'otros';

                        TaxistaDocument::create($data);

                        Notification::make()
                            ->title('Documento creado')
                            ->success()
                            ->send();
                    })
                    ->icon('heroicon-o-plus')
                    ->label('Nuevo documento'),
            ])
            ->filterFormSchema([
                Select::make('taxista_user_id')
                    ->label('Taxista')
                    ->relationship('taxista', 'name')
                    ->multiple(true)
                    ->searchable()
                    ->preload(),

                Select::make('document_type')
                    ->label('Tipo de Documento')
                    ->options(TaxistaDocumentTypes::options())
                    ->multiple(true)
                    ->preload(),

                Select::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo'
                    ])
                    ->multiple(true),
            ])
            ->applyFiltersUsing(function (Builder $query, array $filters): Builder {
                if (!empty($filters['taxista_user_id'])) {
                    $query->whereIn('taxista_user_id', $filters['taxista_user_id']);
                }
                if (!empty($filters['document_type'])) {
                    $query->whereIn('document_type', $filters['document_type']);
                }
                if (!empty($filters['status'])) {
                    $query->whereIn('status', $filters['status']);
                }

                return $query;
            })
            ->recordActions([
                ActionGroup::make([
                    EditAction::make('edit')
                        ->label('')
                        ->model(TaxistaDocument::class)
                        ->modalWidth(Width::FiveExtraLarge)
                        ->schema(function (Schema $schema, TaxistaDocument $record) {
                            return [
                                TextInput::make('title')
                                    ->label('Título')
                                    ->required(),
                                Select::make('document_type')
                                    ->label('Tipo de documento')
                                    ->options(TaxistaDocumentTypes::options())
                                    ->required(),
                                Select::make('taxista_user_id')
                                    ->label('Taxista')
                                    ->relationship('taxista', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                TextInput::make('notas')
                                    ->label('Notas')
                                    ->maxLength(500),
                            ];
                        })
                        ->action(function (array $data, array $arguments) {
                            $documento = TaxistaDocument::find($arguments['id']);
                            if ($documento) {
                                $documento->update($data);
                                Notification::make()
                                    ->title('Actualizado')
                                    ->success()
                                    ->body('Documento actualizado correctamente')
                                    ->send();
                            }
                        }),

                    DeleteAction::make('delete')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger')
                        ->action(function (array $arguments) {
                            $documento = TaxistaDocument::find($arguments['id']);
                            if ($documento) {
                                $documento->delete();
                                Notification::make()
                                    ->title('Eliminado')
                                    ->success()
                                    ->body('Documento eliminado correctamente')
                                    ->send();
                            }
                        }),

                    Action::make('view')
                        ->label('Ver')
                        ->icon('heroicon-o-eye')
                        ->slideOver()
                        ->schema(function ($record) {
                            $documento = TaxistaDocument::find($record['id']);
                            return [
                                TextEntry::make('title')
                                    ->weight(FontWeight::Bold)
                                    ->label('Título'),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('taxista.name')
                                            ->label('Taxista')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-user')
                                            ->copyable(),
                                        TextEntry::make('document_type')
                                            ->label('Tipo')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-document-text')
                                            ->formatStateUsing(fn($state) => TaxistaDocumentTypes::label($state)),
                                        TextEntry::make('status')
                                            ->label('Estado')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-check-circle'),
                                        TextEntry::make('created_at')
                                            ->label('Fecha')
                                            ->weight(FontWeight::Bold)
                                            ->icon('heroicon-o-calendar-days')
                                            ->dateTime('d/m/Y H:i'),
                                    ]),
                                TextEntry::make('notas')
                                    ->label('Notas')
                                    ->visible(fn($record) => !empty($record['notas'])),
                            ];
                        }),
                ])
                    ->dropdownPlacement('bottom-end'),
            ])
            ->columns($columnas);
    }

    public function getTabs(): array
    {
        $todas = TaxistaDocument::query()->count();
        $thisWeek = TaxistaDocument::query()->where('created_at', '>=', now()->subWeek())?->count();
        $thisMonth = TaxistaDocument::query()->where('created_at', '>=', now()->subMonth())?->count();
        $thisYear = TaxistaDocument::query()->where('created_at', '>=', now()->subYear())?->count();

        $cuotas = TaxistaDocument::query()->where('document_type', 'cuotas')?->count();
        $nominas = TaxistaDocument::query()->where('document_type', 'nomina')?->count();
        $repuestos = TaxistaDocument::query()->where('document_type', 'repuestos')?->count();
        $agencias = TaxistaDocument::query()->where('document_type', 'agencias')?->count();
        $impuestos = TaxistaDocument::query()->where('document_type', 'impuesto')?->count();
        $certificados = TaxistaDocument::query()->where('document_type', 'certificado')?->count();
        $seguros = TaxistaDocument::query()->where('document_type', 'seguro')?->count();

        return [
            'todos' => Tab::make()
                ->label('Todos')
                ->badgeColor('success')
                ->badge($todas),
            'nominas' => Tab::make()
                ->label('Nóminas')
                ->badgeColor($nominas > 10 ? 'success' : 'warning')
                ->badge($nominas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'nomina')),
            'cuotas' => Tab::make()
                ->label('Cuotas')
                ->badgeColor($cuotas > 10 ? 'success' : 'warning')
                ->badge($cuotas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'cuotas')),
            'impuestos' => Tab::make()
                ->label('Impuestos')
                ->badgeColor($impuestos > 5 ? 'success' : 'warning')
                ->badge($impuestos)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'impuesto')),
            'repuestos' => Tab::make()
                ->label('Repuestos')
                ->badgeColor($repuestos > 5 ? 'success' : 'warning')
                ->badge($repuestos)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'repuestos')),
            'agencias' => Tab::make()
                ->label('Agencias')
                ->badgeColor($agencias > 5 ? 'success' : 'warning')
                ->badge($agencias)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'agencias')),
            'certificados' => Tab::make()
                ->label('Certificados')
                ->badgeColor($certificados > 5 ? 'success' : 'warning')
                ->badge($certificados)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'certificado')),
            'seguros' => Tab::make()
                ->label('Seguros')
                ->badgeColor($seguros > 5 ? 'success' : 'warning')
                ->badge($seguros)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'seguro')),
        ];
    }

    protected function getViewData(): array
    {
        $tiposdoc = $this->tiposdoc();

        return [
            'tiposdoc' => $tiposdoc,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('Lista Documentos')
                ->url(TaxistaDocumentResource::getUrl('index'))
                ->color('danger')
                ->button()
                ->hiddenLabel(true)
                ->icon('heroicon-o-list-bullets'),
        ];
    }
}
