<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\TaxistaDocuments\Pages;

use App\Enums\DocumentosTipo;
use App\Filament\App\Resources\TaxistaDocuments\TaxistaDocumentResource;
use App\Filament\Clusters\Taxistas\Pdfs\Pages\ListPdfs;
use App\Filament\Clusters\Taxistas\Pdfs\PdfResource;
use App\Filament\Clusters\Taxistas\Pdfs\Schemas\PdfForm;
use App\Filament\Clusters\Taxistas\Pdfs\Schemas\PdfForm2;
use App\Models\Taxi\Cita;
use App\Models\Taxi\Documento;
use App\Models\Taxi\TipoDoc;
use App\Models\Taxi\TipoUsuario;
use App\Models\Taxi\Usuario;
use App\Models\TaxiCentral\DocumentType;
use App\Models\TaxistaDocument;
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

// Import necesario
use Filament\Resources\Pages\PageRegistration;
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
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

final class TaxistaDocumentKanban extends KanbanPage
{
    use HasTabs;

    public static function route(string $path): PageRegistration
    {
        return new PageRegistration(
            page: static::class,
            route: fn(Panel $panel): Route => RouteFacade::get($path, static::class)
                ->middleware(static::getRouteMiddleware($panel))
                ->withoutMiddleware(static::getWithoutRouteMiddleware($panel)),
        );
    }

    public $tiposdoc;
    public ?array $data = [];

    protected static string $model = TaxistaDocument::class;

    protected static string $resource = TaxistaDocumentResource::class;

    protected static string $statusEnum = DocumentosTipo::class;

    protected static string $recordTitleAttribute = 'meta.referencia';

    protected static string $recordStatusAttribute = 'document_type';

    protected static string $columnHeaderComponent = 'advanced-kanban::column-header_doc';

    protected static string $cardComponent = 'advanced-kanban::card_doc';

    public function with(): array
    {
        return [
            //'tiposdoc' => $this->tiposdoc(),
        ];
    }

    public function addAction(): \Asmit\AdvancedKanban\RecordAction\Action
    {
        return Action::make('docs')
            ->schema(function (array $arguments): array {
                dd($arguments);
                return [
                    TextInput::make('title')->default($arguments['id']),
                ];
            });
    }

    public function create(): void
    {
        dd($this->data);
        $data = $this->form->getState();
        $date = Carbon::parse($data['date']);
        [$trackId, $hour] = explode('-', $data['track']);
        $startTime = $date->copy()->hour($hour);
        $endTime = $startTime->copy()->addHour();
        $dateTimeFormat = 'Y-m-d H:i:s';

        $cita = Documento::create($data);

        // Save the relationships from the form to the post after it is created.
        $this->form->model($cita)->saveRelationships();
        $this->form->fill();

    }

    #[Computed]
    public function tiposdoc()
    {
        return DocumentType::query()
            ->withWhereHas(
                'documentos', function ($query) {
                $query->where('documentos.estado', 1);
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
        $tipo_id = '';
        foreach ($tipos as $tipo) {
            if ($tipo['slug'] !== null) {
                $slug = $tipo['slug'];
                $tipo_id = $tipo['id'];
            }
            // echo $tipo['slug'].'heroicon-s-'.DocumentosTipo::getIconBySlug($tipo['slug']).'<br>';
            $columnas[] = KanbanColumn::make($slug)
                ->hidden(false)
                ->iconcolor($tipo['color'])
                ->icon($tipo['icono'])
                ->label(fn() => ucfirst($tipo['nombre']))
                ->description('Gestión de ' . $tipo['nombre'])
                ->modifyRecordQueryUsing(function ($query) use ($tipo_id) {
                    return $query->where('document_type', $tipo_id)->orderBy('id', 'desc');
                });
        }

        $kanban22 = $kanban
            ->model(TaxistaDocument::class)
            ->statusField('type')
            ->titleField('titulo')
            ->descriptionField('notas')
            ->searchableFields(['referencia', 'mes', 'type', 'favorito'])
            ->enableLoadingIndicator()
            ->enableFilterIndicator()
            ->columnHeaderActions([
                CreateAction::make('nueva')
                    ->schema(function (Schema $schema, array $arguments) {
                        $schema = PdfForm2::configure($schema, $arguments['status']);

                        return $schema;
                    })
                    ->action(function ($arguments, $data) {
                        $data[] = ['usuario_id' => auth()->user()->id];
                        $doc = Documento::create($data);
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

                Select::make('document_type')
                    ->label('Tipo de Documento')
                    ->relationship('tipo', 'nombre')
                    ->multiple(true)
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
                if (!empty($filters['document_type'])) {
                    $query->whereIn('tipo_id', $filters['tipo_id']);
                }
                if (!empty($filters['usuario_id'])) {
                    $query->where('usuario_id', $filters['usuario_id']);
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
                            $schema = PdfForm::configure($schema);

                            return $schema;
                        })
                        ->action(function (array $data, array $arguments) {

                            if (isset($data['id'])) {
                                $cita = TaxistaDocument::find($data['id']);
                                if ($cita) {
                                    $cita->update($data);
                                } else {
                                    $cita = TaxistaDocument::create($data);
                                }
                                Notification::make()
                                    ->title('Actualizado')
                                    ->success()
                                    ->body('Your bid has successfully been sent')
                                    ->send();
                            }
                        }),

                    DeleteAction::make('delete')
                        ->icon('heroicon-o-trash')
                        ->requiresConfirmation()
                        ->color('danger'),

                    Action::make('view')
                        ->label('View')
                        ->icon('heroicon-o-eye')
                        ->slideOver()
                        ->action(function (array $data, array $arguments) {
                            $cita = Documento::find($arguments['id']);
                        })
                        ->modalSubmitAction(false)
                        ->schema(function ($record) {
                            return [
                                TextEntry::make('referencia')
                                    ->weight(FontWeight::Bold)
                                    ->hiddenLabel(),
                                Grid::make(2)
                                    ->schema([
                                        TextEntry::make('taxista.name')
                                            ->hint('Usuario')
                                            ->weight(FontWeight::Bold)
                                            // ->inlineLabel()
                                            ->hiddenLabel()
                                            ->icon('heroicon-o-user')
                                            ->copyable(),

                                        TextEntry::make('document_type')
                                            ->hiddenLabel()
                                            ->icon(Heroicon::OutlinedRectangleStack)
                                            ->weight(FontWeight::Bold)
                                            ->hint('tipodoc'),
                                        TextEntry::make('created_at')
                                            ->hiddenLabel()
                                            ->weight(FontWeight::Bold)
                                            ->hint('Fecha')
                                            ->icon('heroicon-o-calendar-days'),
                                    ]),

                            ];
                        }),
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

        $todas = TaxistaDocument::query()->count();
        $thisWeek = TaxistaDocument::query()->where('created_at', '>=', now()->subWeek())?->count();
        $thisMonth = TaxistaDocument::query()->where('created_at', '>=', now()->subMonth())?->count();
        $thisYear = TaxistaDocument::query()->where('created_at', '>=', now()->subYear())?->count();

        $cuotas = TaxistaDocument::query()->where('document_type', 'cuota')?->count();
        $nominas = TaxistaDocument::query()->where('document_type', 'nomina')?->count();
        $repuestos = TaxistaDocument::query()->where('document_type', 'repuesto')?->count();
        $agencias = TaxistaDocument::query()->where('document_type', 'agencia')?->count();

        return [
            'DOCS.' => Tab::make()
                ->badgeColor('success')
                ->badge($todas),
            'cuota' => Tab::make()
                ->badgeColor($cuotas > 10 ? 'success' : 'warning')
                ->badge($cuotas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'cuota')),
            'nominas' => Tab::make()
                ->badgeColor($nominas > 10 ? 'success' : 'warning')
                ->badge($nominas)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'nomina')),
            'repuestos' => Tab::make()
                ->badgeColor($repuestos > 10 ? 'success' : 'warning')
                ->badge($repuestos)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'repuesto')),
            'agencias' => Tab::make()
                ->badgeColor($agencias > 10 ? 'success' : 'warning')
                ->badge($agencias)
                ->modifyQueryUsing(fn(Builder $query) => $query->where('document_type', 'agencia')),


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


            Action::make('Task List')
                ->url(ListPdfs::getUrl())
                ->color('danger')
                ->Button()
                ->hiddenLabel(true)
                ->icon(Heroicon::ListBullet),

        ];
    }
}
