<?php

namespace App\Filament\App\NovaHub\Resources\Pagos;

use App\Filament\Forms\Components\SelectPlus;
use App\Models\Taxi\PagoServicio;
use App\Services\PagoReferenciaService;
use ApproTickets\Models\Refund;
use Cheesegrits\FilamentGoogleMaps\Actions\GoToAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\HasKeyBindings;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Grouping\Group as TableGroup;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

// añade este import junto al resto
use App\Enums\DocumentoStatus;
use App\Enums\EstadoPago;
use App\Enums\PagoEstado;
use App\Events\DocumentoCompleted;
use App\Filament\App\NovaHub\Resources\Tasks\TaskResource;
use App\Filament\Clusters\Taxistas;
use App\Filament\App\NovaHub\Resources\Pagos\Pages;
use App\Filament\App\NovaHub\Resources\Pagos\RelationManagers;
use App\Filament\Support\baseresource;
use App\Models\Taxi\Banner\Category;
use App\Models\Taxi\Banner\Content;
use App\Models\Taxi\Documento;
use App\Models\Taxi\Pago;
use App\Models\Taxi\Taxista;
use Illuminate\Support\Facades\Route;

use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema as Form;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\BulkActionGroup;


use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Actions\CreateAction;

use Filament\Actions\ReplicateAction;
use Filament\Tables\Filters\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Archilex\AdvancedTables\Filters\SelectFilter;
use Archilex\AdvancedTables\Filters\TextFilter;
use Archilex\AdvancedTables\Filters\AdvancedFilter;
use Archilex\AdvancedTables\Filters\BooleanFilter;
use Archilex\AdvancedTables\Filters\NumericFilter;
use Archilex\AdvancedTables\Filters\DateFilter;
use Archilex\AdvancedTables\Filters\UserSelectFilter;
use Archilex\ToggleIconColumn\Columns\ToggleIconColumn;
use Filament\Pages\Enums\SubNavigationPosition;
use TomatoPHP\FilamentAlerts\Filament\Resources\NotificationsTemplateResource;

use BackedEnum;
use App\Filament\App\NovaHub\Resources\TaxistasCluster\TaxistasCluster;
use function Amp\Http\Server\redirectTo;
use function Clue\StreamFilter\fun;
use function Livewire\after;
use function Pest\Laravel\post;
use function PHPUnit\Framework\returnArgument;
use function Symfony\Component\Translation\t;
use Illuminate\Support\Js;
use App\Filament\App\NovaHub\Resources\Pagos\RelationManagers\PagosRelationManager2;
use UnitEnum;

class PagoResource extends baseresource
{
    protected $js;
    protected static ?string $model = Pago::class;

    // protected static string | BackedEnum | null $navigationIcon  = Heroicon::OutlinedRectangleStack;
    protected static string | UnitEnum | null $navigationGroup = 'Taxis';
    protected static ?string $navigationLabel = "Pagos";

    protected static ?int $navigationSort = -1;

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';   // ← Acción por defecto al hacer clic en una fila
    }

    public static function calculateFinalAmount($get, $set)
    {
        $totalAmount = 0;
        $previousItems = $get('pagos_details') ?? [];

        $invoiceItems = collect($get('pagos'))->map(function ($item, $key) use ($set, &$totalAmount, $previousItems) {

            $item['id'] = $key;

            $product = PagoServicio::find($item['id']);
            $item['importe'] = isset($product->importe) ? $product->importe : 0;

            $itemTotal = $item['importe'];
            $totalAmount += $itemTotal;

            return array_merge($item, ['importe' => number_format($itemTotal, 2, '.', '')]);
        })->toArray();

        $set('pagos_details', $invoiceItems);
        $set('pagos', $invoiceItems);
        $subTotal = collect($invoiceItems)->sum(fn($item) => (float)$item['importe']);
        $set('total', number_format($subTotal, 2, '.', ''));

        return $subTotal;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Tabs::make('Detalles de Pago')
                    ->tabs([
                        Tab::make('Servicio')
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Section::make('Detalles Servicio')
                                    ->icon('taxi')
                                    ->collapsed(true)
                                    ->schema([
                                        Forms\Components\TextInput::make('referencia')
                                            ->maxLength(100)
                                            ->default(null),
                                        Forms\Components\Select::make('status')
                                            ->label('Estado')
                                            ->options([
                                                PagoEstado::options()
                                            ])
                                            ->default(1),
                                        Forms\Components\TextInput::make('recogida')
                                            ->label('Lugar')
                                            ->maxLength(100)
                                            ->default('Famara'),
                                        Forms\Components\TextInput::make('personas')
                                            ->label('Pers.')
                                            ->numeric()
                                            ->default(2),
                                    ])
                                    ->compact()
                                    ->columns(4),
                                Section::make('Cliente')
                                    ->collapsed(true)
                                    ->icon(Heroicon::OutlinedClipboard)
                                    ->columns(3)
                                    ->schema([

                                        Forms\Components\TextInput::make('nombre')
                                            ->label('Cliente')
                                            ->maxLength(255)
                                            ->default('Patrick'),
                                        Forms\Components\TextInput::make('telefono')
                                            ->label('Telefono')
                                            ->tel()
                                            ->maxLength(155)
                                            ->default('646426442'),
                                        Forms\Components\TextInput::make('email')
                                            ->label('Email')
                                            ->email()
                                            ->maxLength(155)
                                            ->default('patrickms@gmail.com'),

                                    ]),
                                Section::make('Importe y Pago')
                                    ->icon('euro')
                                    ->columns(2)
                                    ->columnSpanFull()
                                    ->live(true)
                                    ->visible(fn(Get $get): bool => $get('status') !== null)
                                    ->schema([
                                        Section::make('')
                                            ->live()
                                            ->columns(3)
                                            ->schema([
                                                TextInput::make('importe')
                                                    ->label('Imp. Depósito'),
                                                TextInput::make('total')
                                                    ->label('Importe Total'),
                                                TextInput::make('pagado')
                                                    ->label('Importe Pagado'),
                                                //->afterStateHydrated(fn($operation, $get, $set, $state) => $operation == 'edit' ? self::calculateFinalAmount($get, $set) : $set('total', $state)),

                                            ]),

                                        /*Repeater::make('pagos')
                                            ->columnSpan(1)
                                            ->schema([

                                                        Forms\Components\TextInput::make('id')
                                                            ->columns(1),
                                                        DateTimePicker::make('fecha_pago')
                                                            ->time(true)
                                                            ->label('F. Pago')
                                                            ->displayFormat('d/m H:i')
                                                            ->native(false)
                                                            ->default(now()->addDays(0)->addHour()),
                                                        Forms\Components\TextInput::make('ref_pago')
                                                            ->maxLength(155)
                                                            ->columns(1),
                                                        Forms\Components\TextInput::make('importe')
                                                            ->label('Importe')
                                                            ->required()
                                                            ->live()
                                                            ->extraInputAttributes(['oninput' => "this.value = this.value.replace(/[e\+\-]/gi, '')"])
->afterStateUpdated(
    fn($operation, $get, $set, $state) => $operation == 'edit' ? self::calculateFinalAmount($get, $set) : $set('total', $state)
)
                                                            ->reactive()
                                                            ->columnSpan('lg')
                                                            ->default(0)
                                                            ->suffixActions([
                                                                Action::make('resto')
                                                                    ->icon(Heroicon::CurrencyEuro)
                                                                    ->size('md')
                                                                    ->action(function (Set $set, $state,Pago|null $record,Get $get) {
                                                                        if($record != null) {
                                                                            $set('pagado', $record->importe - $record->pagado);
                                                                        }else $set('pagado',$get('importe'));
                                                                        return $get('pagado');
                                                                    }),
                                                                Action::make('mitad')
                                                                    ->icon(Heroicon::CurrencyEuro)
                                                                    ->size('xs')
                                                                    ->action(function (Set $set, $state,Pago|null $record,Get $get) {
                                                                        if($record != null) {
                                                                            $set('pagado', $record->importe - $record->pagado);
                                                                        }else $set('pagado',$get('importe')/2);
                                                                        return $get('pagado');
                                                                    }),
                                                                Action::make('todo')

                                                                    ->label('Pagar')
                                                                    ->icon(Heroicon::OutlinedCreditCard)
                                                                    ->size('xs')
                                                                    ->action(function (Action $action, $set, $state,Pago|null $record,Get $get) {
                                                                        $idPago = "";
                                                                        if($record == null) {
                                                                            $id = "";
                                                                            $referencia = PagoReferenciaService::generateUsingNextAutoIncrement();
                                                                            $nuevoPagado = $get('pagado');
                                                                            $updateData = [
                                                                                'pagado' => $nuevoPagado,
                                                                                'metodo_pago' => 'R',
                                                                                'importe' => $get('importe'),
                                                                                'status' => PagoEstado::PENDIENTE,
                                                                                'notificado' => $get('notificado'),
                                                                                'fecha_pago' => Carbon::now()->addDays(1)->addHour(2)->format('Y-m-d H:i:s'),
                                                                                'ref_pago' => 'PAG-' . mb_strtoupper(mb_substr(md5(time()), 0, 8)),
                                                                                'usuario_id' => auth()->id(),
                                                                            ];

                                                                            if ($nuevoPagado >= $get("importe")) {
                                                                                $updateData['status'] = PagoEstado::PAGADO;
                                                                            } elseif ($nuevoPagado > 0) {
                                                                                $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                                                            } else {
                                                                                $updateData['status'] = PagoEstado::PENDIENTE;
                                                                            }
                                                                            // Actualizar primero en BD

                                                                            $idPago = Pago::insertGetId($updateData);

                                                                        }else
                                                                            $idPago = $record->id;


                                                                        Notification::make()
                                                                            ->title('Abriendo Redsys')
                                                                            ->body('Redirigiendo al TPV en una nueva pestaña...')
                                                                            ->success()
                                                                            ->send();


                                                                        $url = route('redsys.pay.fromPago', ['pago' => $idPago]);
                                                                        return redirect($url);
                                                                        return $action->url(
                                                                            function (Pago $record,$idPago) {

                                                                                return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                                                            }, shouldOpenInNewTab: true);

                                                                    }),


                                                            ]),

                                                        Select::make('metodo_pago')
                                                            ->label('Metodo Pago')
                                                            ->options([
                                                                'C' => 'Contado',
                                                                'T' => 'Transf.',
                                                                'R' => 'Redsys',
                                                            ])
                                                            ->default('C'),


                                                    ])
                                            ->columns(4)
                                            ->compact(),
                                    ])
                                    ->columns(2),*/

                                        /*Actions::make([
                                            Action::make('add_note_term')
                                                ->icon(Heroicon::OutlinedPlus)
                                                ->label(__('messages.quote.add_note_term'))
                                                ->hidden(fn($get) => $get('open_term') || !empty($get('note')) || !empty($get('term')))
                                                ->action(function ($set) {
                                                    $set('note', '');
                                                    $set('term', '');
                                                    $set('open_term', true);
                                                })
                                                ->color('primary'),

                                            Action::make('remove_note_term')
                                                ->icon(Heroicon::OutlinedMinus)
                                                ->label(__('messages.quote.remove_note_term'))
                                                ->hidden(fn($get) => (!$get('open_term') && empty($get('note')) && empty($get('term'))))
                                                ->action(function ($set) {
                                                    $set('note', '');
                                                    $set('term', '');
                                                    $set('open_term', false);
                                                })
                                                ->color('danger')
                                        ])->columnSpanFull()->live(),

                                        Group::make([
                                            Textarea::make('note')
                                                ->live()
                                                ->placeholder(__('messages.quote.note'))
                                                ->label(__('messages.quote.note') . ':'),

                                            Textarea::make('term')
                                                ->live()
                                                ->placeholder(__('messages.quote.terms'))
                                                ->label(__('messages.quote.terms') . ':'),
                                        ])->columns(2)->columnSpanFull()->visible(fn($get) => $get('open_term') || !empty($get('note')) || !empty($get('term'))),*/
                                    ]),
                            ]),


                        Tab::make('Fechas')
                            ->icon(Heroicon::OutlinedCalendar)
                            ->schema([
                                Section::make('')
                                    ->columns(4)
                                    ->compact()
                                    ->schema([
                                        DateTimePicker::make('fecha_servicio')
                                            ->time(true)
                                            ->label('F. Servicio')
                                            ->displayFormat('d/m/Y H:i:s')
                                            ->default(now()->addDays(1))
                                            ->native(false)
                                            ->nullable(),
                                        DateTimePicker::make('fecha_terminado')
                                            ->time(true)
                                            ->label('F. Terminado')
                                            ->native(false)
                                            ->displayFormat('d/m/Y H:i:s')
                                            ->default(now()->addDays(1)->addHour())
                                            ->nullable()
                                            ->afterOrEqual('fecha_servicio'),
                                        DateTimePicker::make('fecha_notificado')
                                            ->time(true)
                                            ->label('F. Notificado')
                                            ->native(false)
                                            ->displayFormat('d/m/Y H:i:s')
                                            ->default(now()->addDays(1))
                                            ->nullable(),

                                    ]),
                            ]),

                        Tab::make('Taxista')
                            ->icon('taxi')
                            ->columns(2)
                            ->schema([
                                Select::make('usuario_id')
                                    ->label('Usuario')
                                    ->relationship('usuario', 'nombre')->searchable()
                                    ->optionsLimit(
                                        fn(Builder $query): Builder => $query->where('id', auth()->id())
                                    )
                                    ->default(auth()->id()),


                                Select::make('taxista_id')
                                    ->label('Taxista')
                                    ->relationship('taxista', 'nombre')->searchable()
                                    ->default(null),
                            ]),

                    ])
                    ->columnSpanFull(),

            ]);
    }

    public static function table(Table $table): Table
    {
        $tabla = $table;

        return $table
            ->defaultSort('id', 'desc')
            ->columns([

                Tables\Columns\TextColumn::make('id')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->toggleable(true)
                    ->label('ID')
                    ->badge()
                    ->sortable(),
                TextColumn::make('referencia')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('Ref./R.Pago')
                    ->formatStateUsing(function ($state, $record) {
                        if ($record->ref_pago != null)
                            return $state . '/' . "<br>" . $record->ref_pago;
                        else
                            return $state;
                    })
                    ->extraAttributes(['style' => 'font-weight: bold']) // Aplica negritas
                    ->searchable()
                    ->sortable()
                    ->html(true)
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('nombre')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->toggleable(true)
                    ->label('Cliente')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(true)
                    ->sortable(),
                Tables\Columns\TextColumn::make('importe')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->sortable()
                    ->toggleable(true)
                    ->label('Imp. Total/Pagado')
                    ->formatStateUsing(fn($state, $record) => $state . ' € / ' . $record->pagado . ' €')
                    ->searchable(),
                Tables\Columns\TextColumn::make('metodo_pago')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('M. Pago')
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('status')
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->label('status')
                    ->sortable()
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('recogida')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Lugar')
                    ->sortable()
                    ->toggleable(true),

                /*CircleProgress::make('circle')
                    ->getStateUsing(function ($record) {
                        $total = $record->items()->count();
                        $progress = $record->countPaidItems();
                        return [
                            'total' => $total,
                            'progress' => $progress,
                        ];
                    })
                    ->hideProgressValue(),

                ProgressBar::make('bar')
                    ->getStateUsing(function (App\Models\Taxi\Note $record) {
                        $total = $record->items()->count();
                        $progress = $record->countPaidItems();
                        return [
                            'total' => $total,
                            'progress' => $progress,
                        ];
                    })
                    ->hideProgressValue(),*/

                Tables\Columns\TextColumn::make('email')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('personas')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Pers.')
                    ->numeric()
                    ->sortable()
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('taxista.nombre')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(true)
                    ->label('Taxista')
                    ->sortable(),
                Tables\Columns\TextColumn::make('usuario.nombre')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->toggleable(true)
                    ->label('Usuario')
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_servicio')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('F. Servicio')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('notificado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Notificado')
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('fecha_terminado')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('F. Terminado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('fecha_alta')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('F. Alta')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true),

                Tables\Columns\TextColumn::make('updated_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true),
                Tables\Columns\TextColumn::make('created_at')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->dateTime()
                    ->sortable()
                    ->toggleable(true),
            ])
            ->filters([
                AdvancedFilter::make(),
            ])
            ->recordActions([

                //EditAction::make(),

                EditAction::make("edita")
                    ->iconButton()
                    ->icon(Heroicon::OutlinedPencilSquare)
                    ->extraModalFooterActions(function (Pago $record, EditAction $action): array {
                        return [


                            $action->getModalSubmitAction()
                                ->label('Guardar')
                                ->icon(Heroicon::OutlinedCreditCard)
                                ->color('warning')
                                ->close(false)
                                ->openUrlInNewTab(false)
                                ->action(function (Pago $record, array $data, $action) {
                                    $nuevoPagado = $record->pagado + (float)($data['pagado'] ?? 0);

                                    $updateData = [
                                        'pagado' => $nuevoPagado,
                                        'metodo_pago' => 'R',
                                        'status' => PagoEstado::PENDIENTE,
                                        'ref_pago' => $record->ref_pago,
                                        'usuario_id' => $record->usuario_id,
                                        'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];

                                    if ($nuevoPagado >= $record->importe) {
                                        $updateData['status'] = PagoEstado::PAGADO;
                                    } elseif ($nuevoPagado > 0) {
                                        $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                    } else {
                                        $updateData['status'] = PagoEstado::PENDIENTE;
                                    }
                                    // Actualizar primero en BD
                                    $record->updateOrFail($updateData);


                                    Notification::make()
                                        ->title('Abriendo Redsys')
                                        ->body('Redirigiendo al TPV en una nueva pestaña...')
                                        ->success()
                                        ->send();

                                    $url = route('redsys.pay.fromPago', ['pago' => $record->id]);

                                    dd($url);
                                    return redirectTo($url);
                                    $action->url(
                                        function (Pago $record) {
                                            return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                        }, shouldOpenInNewTab: true);
                                    $url = route('redsys.pay.fromPago', ['pago' => $record->id]);
                                }),

                            Action::make('cancelar')
                                ->label('Eliminar')
                                ->modal(false) // esta acción no abre su propio modal
                                ->requiresConfirmation()
                                ->icon(Heroicon::OutlinedTrash)
                                ->color('danger')
                                ->action(function (Pago $record, array $data, Action $action, $livewire) {
                                    $record->delete();

                                    // Marca como exitosa para que se cierre con ->close()
                                    $action->success();

                                    $livewire->dispatch('close-modal', id: 'fi-' . $action->getLivewire()->getId() . '-action-0');

                                }),

                            Action::make('pagar')
                                //->requiresConfirmation()
                                ->icon('euro')
                                ->visible(false)
                                ->label('Redsys')
                                ->keyBindings(['mod+p'])
                                ->openUrlInNewTab(true)
                                ->action(function (Pago $record, array $data, $action) {
                                    $nuevoPagado = $record->pagado + (float)($data['pagado'] ?? 0);

                                    $updateData = [
                                        'pagado' => $nuevoPagado,
                                        'metodo_pago' => 'R',
                                        'status' => PagoEstado::PENDIENTE,
                                        'ref_pago' => $record->ref_pago,
                                        'usuario_id' => $record->usuario_id,
                                        'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];

                                    if ($nuevoPagado >= $record->importe) {
                                        $updateData['status'] = PagoEstado::PAGADO;
                                    } elseif ($nuevoPagado > 0) {
                                        $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                    } else {
                                        $updateData['status'] = PagoEstado::PENDIENTE;
                                    }
                                    // Actualizar primero en BD
                                    $record->updateOrFail($updateData);

                                    Notification::make()
                                        ->title('Abriendo Redsys')
                                        ->body('Redirigiendo al TPV en una nueva pestaña...')
                                        ->success()
                                        ->send();
                                    $action->url(
                                        function (Pago $record) {
                                            return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                        }, shouldOpenInNewTab: true);

                                    // Abrir Redsys sin cerrar el modal

                                }),


                            $action->getModalCancelAction()
                                ->label('Cerrar')
                                ->icon(Heroicon::OutlinedXMark)
                                ->color('secondary'),
                        ];
                    }),

                EditAction::make('add-pay')
                    ->icon('euro+')
                    ->iconButton()
                    ->visible(fn($record) => !in_array($record->status, [PagoEstado::PAGADO, PagoEstado::PAGADO_ADMIN, PagoEstado::PAGADO_TPV, PagoEstado::CANCELADO]))
                    ->openUrlInNewTab(true)
                    ->closeModalByClickingAway(false)
                    ->extraModalFooterActions(function (Pago $record, Action $action): array {
                        return [
                            $action->getModalSubmitAction()
                                ->label('Ir a Redsys 4')
                                ->openUrlInNewTab(true)
                                ->icon(Heroicon::OutlinedCreditCard)
                                ->color('warning')
                                ->close(false)
                                ->action(function (Pago $record, array $data, $action) {
                                    $nuevoPagado = $record->pagado + (float)($data['pagado'] ?? 0);

                                    $updateData = [
                                        'pagado' => $nuevoPagado,
                                        'metodo_pago' => 'R',
                                        'status' => PagoEstado::PENDIENTE,
                                        'ref_pago' => $record->ref_pago,
                                        'usuario_id' => $record->usuario_id,
                                        'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];

                                    if ($nuevoPagado >= $record->importe) {
                                        $updateData['status'] = PagoEstado::PAGADO;
                                    } elseif ($nuevoPagado > 0) {
                                        $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                    } else {
                                        $updateData['status'] = PagoEstado::PENDIENTE;
                                    }
                                    // Actualizar primero en BD
                                    $record->updateOrFail($updateData);

                                    Notification::make()
                                        ->title('Abriendo Redsys')
                                        ->body('Redirigiendo al TPV en una nueva pestaña...')
                                        ->success()
                                        ->send();

                                    // Abrir Redsys sin cerrar el modal
                                    $url = route('redsys.pay.fromPago', ['pago' => $record->id]);
                                    $action->url(
                                        function (Pago $record) {
                                            return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                        }, shouldOpenInNewTab: true);
                                    return $url;
                                }),
                        ];
                    })
                    ->schema([
                        Section::make(function (Pago $record) {
                            $pendiente = $record->importe - $record->pagado;
                            return "Pedido: {$record->referencia} | Pendiente: {$pendiente}€ de {$record->importe}€";
                        })
                            ->description('Selecciona el método e ingresa el importe a cobrar')
                            ->schema([
                                Forms\Components\Hidden::make('ref_pago')
                                    ->default(fn() => 'PAG-' . mb_strtoupper(mb_substr(md5(time()), 0, 8))),

                                Forms\Components\TextInput::make('pagado')
                                    ->label('Importe a cobrar')
                                    ->numeric()
                                    ->required()
                                    ->formatStateUsing(function ($state, $record) {
                                        /*                                        dd($state);*/
                                    })
                                    ->suffix('€')
                                    ->suffixIcon(Heroicon::OutlinedCurrencyEuro)
                                    ->suffixActions([
                                        Action::make('resto')
                                            ->icon(Heroicon::Banknotes)
                                            ->tooltip('Cobrar el resto')
                                            ->action(function (Set $set, Pago $record) {
                                                return $set('pagado', $record->importe - $record->pagado);
                                            }),
                                        Action::make('mitad')
                                            ->icon(Heroicon::CurrencyEuro)
                                            ->tooltip('Cobrar la mitad del resto')
                                            ->action(function (Set $set, Pago $record) {
                                                return $set('pagado', ($record->importe - $record->pagado) / 2);
                                            }),
                                        Action::make('pagar')
                                            //->requiresConfirmation()
                                            ->icon('euro')
                                            ->label('Pagar')
                                            ->keyBindings(['mod+p'])
                                            ->openUrlInNewTab(true)
                                            ->action(function (Pago $record, array $data, $action) {
                                                $nuevoPagado = $record->pagado + (float)($data['pagado'] ?? 0);

                                                $updateData = [
                                                    'pagado' => $nuevoPagado,
                                                    'metodo_pago' => 'R',
                                                    'status' => PagoEstado::PENDIENTE,
                                                    'ref_pago' => $record->ref_pago,
                                                    'usuario_id' => $record->usuario_id,
                                                    'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ];

                                                if ($nuevoPagado >= $record->importe) {
                                                    $updateData['status'] = PagoEstado::PAGADO;
                                                } elseif ($nuevoPagado > 0) {
                                                    $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                                } else {
                                                    $updateData['status'] = PagoEstado::PENDIENTE;
                                                }
                                                // Actualizar primero en BD
                                                $record->updateOrFail($updateData);

                                                Notification::make()
                                                    ->title('Abriendo Redsys')
                                                    ->body('Redirigiendo al TPV en una nueva pestaña...')
                                                    ->success()
                                                    ->send();

                                                $action->url(
                                                    function (Pago $record) {
                                                        return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                                    }, shouldOpenInNewTab: true);
                                                // Abrir Redsys sin cerrar el modal

                                            }),
                                    ])
                                    ->default(fn(Pago $record) => $record->importe - $record->pagado),

                                Forms\Components\ToggleButtons::make('metodo_pago')
                                    ->label('Método de pago')
                                    ->options([
                                        'C' => 'Efectivo',
                                        'T' => 'Transferencia',
                                        'R' => 'Redsys (TPV)',
                                        'Z' => 'Bizum',
                                    ])
                                    ->default('R')
                                    ->inline()
                                    ->live(), // Necesario para mostrar/ocultar "Ir a Redsys"

                                Forms\Components\DateTimePicker::make('fecha_pago')
                                    ->visible(false)
                                    ->default(now()),
                            ])
                            ->columns(2)
                            ->compact(),
                    ])
                    // Submit principal SOLO para métodos sin redirección (no Redsys)
                    ->modalSubmitActionLabel('Guardar')
                    ->modalHeading('Pago')
                    ->modalSubheading('Ingresa el importe a cobrar')
                    ->livewireClickHandlerEnabled(true)
                    ->successNotificationTitle('Pago procesado'),

                Action::make('refund')
                    ->icon('euro-')
                    ->iconButton()
                    ->requiresConfirmation()
                    ->modalHeading('')
                    ->modalSubheading('')
                    ->modalContent(fn(Pago $record) => new HtmlString("Importe total {$record->pagado} €"))
                    ->schema(function (Pago $record) {
                        return [

                            Forms\Components\TextInput::make('importe')
                                ->label('Cantidad a devolver')
                                ->required()
                                ->live()
                                ->live()
                                ->suffix(' €')
                                ->default(
                                    function (Pago $record, Get $get, Set $set) {
                                        $set('pagado', $record->pagado);
                                        return $get('pagado');
                                    }
                                )
                                ->suffixActions([

                                        Action::make('resto')
                                            ->icon(Heroicon::CurrencyEuro)
                                            ->size('md')
                                            ->action(function (Set $set, $state, Pago $record, Get $get) {
                                                $set('pagado', $record->importe - $record->pagado);

                                                return $get('pagado');
                                            }),
                                        Action::make('mitad')
                                            ->icon(Heroicon::CurrencyEuro)
                                            ->size('sm')
                                            ->action(function (Set $set, $state, Pago $record, Get $get) {
                                                $set('pagado', ($record->importe - $record->pagado) / 2);
                                                return $get('pagado');
                                            }),
                                        Action::make('todo')
                                            ->icon(Heroicon::CurrencyEuro)
                                            ->size('xs')
                                            ->action(function (Set $set, $state, Pago $record, Get $get) {
                                                $set('pagado', $get('importe'));
                                                $nuevoPagado = $record->pagado + $get('importe');

                                                $updateData = [
                                                    'pagado' => $nuevoPagado,
                                                    'metodo_pago' => 'R',
                                                    'status' => PagoEstado::PENDIENTE,
                                                    'ref_pago' => $record->ref_pago,
                                                    'usuario_id' => $record->usuario_id,
                                                    'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                                ];

                                                if ($nuevoPagado >= $record->importe) {
                                                    $updateData['status'] = PagoEstado::PAGADO;
                                                } elseif ($nuevoPagado > 0) {
                                                    $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                                } else {
                                                    $updateData['status'] = PagoEstado::PENDIENTE;
                                                }
                                                // Actualizar primero en BD
                                                $record->updateOrFail($updateData);

                                                Notification::make()
                                                    ->title('Abriendo Redsys')
                                                    ->body('Redirigiendo al TPV en una nueva pestaña...')
                                                    ->success()
                                                    ->send();
                                                return $get('pagado');
                                            }),


                                    ]
                                )
                        ];
                    })
                    ->action(function (Pago $record, array $data) {
                        $importe = $record->pagado - $data["importe"];
                        if (($record->importe > $record->pagado) && ($importe > 0)) {
                            $record->where('id', $record->id)->update(['referencia' => $record->refID($record->id), 'pagado' => $importe, 'status' => PagoEstado::PAGO_PARCIAL]);
                        } else {
                            $record->where('id', $record->id)->update(['referencia' => $record->refID($record->id), 'pagado' => $importe, 'status' => PagoEstado::PENDIENTE]);
                        }

                        // $refundRequest = RefundController::requestRefund($refund);
                        Notification::make()
                            ->title("Devolució creada.")
                            ->success()
                            ->send();
                        // if ($refundRequest['error']) {
                        //     Notification::make()
                        //         ->title('Error en la petició de devolució')
                        //         ->body($refundRequest['error'])
                        //         ->danger()
                        //         ->send();
                        // } else {
                        //     Notification::make()
                        //         ->title($refundRequest['message'])
                        //         ->success()
                        //         ->send();
                        // }
                    }),

                EditAction::make('pay')
                    ->icon(Heroicon::OutlinedCreditCard)
                    ->iconButton()
                    ->visible(fn($record) => !in_array($record->status, [PagoEstado::PAGADO, PagoEstado::PAGADO_ADMIN, PagoEstado::PAGADO_TPV, PagoEstado::CANCELADO]))
                    ->openUrlInNewTab(true)
                    ->closeModalByClickingAway(false)
                    ->modalSubmitActionLabel('Guardar')
                    ->modalHeading('Pago')
                    ->modalSubheading('Ingresa el importe a cobrar')
                    ->livewireClickHandlerEnabled(true)
                    ->url(
                        function (Pago $record) {
                            return route('redsys.pay.fromPago', ['pago' => $record->id]);
                        }, shouldOpenInNewTab: true)
                    ->successNotificationTitle('Pago procesado'),

                Action::make('Pagado')
                    ->hiddenLabel()
                    ->visible(fn($record) => !in_array($record->status, [PagoEstado::PAGADO, PagoEstado::PAGADO_ADMIN, PagoEstado::PAGADO_TPV, PagoEstado::CANCELADO]))
                    ->icon(Heroicon::Banknotes)
                    ->requiresConfirmation()
                    ->action(function (Pago $record) {
                        $record->status = PagoEstado::PAGADO;
                        $record->pagado = $record->importe;
                        $record->save();
                    }),


            ])
            ->groups(
                [
                    TableGroup::make('usuario.nombre')
                        ->label('Usuario')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('departamento.nombre')
                        ->label('Departamento')
                        ->titlePrefixedWithLabel(true)
                        ->collapsible(true),
                    TableGroup::make('status')
                        ->label('Estado')
                        ->titlePrefixedWithLabel(false)
                        ->collapsible(true),
                ]
            )
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            PagosRelationManager2::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagos::route('/'),
            'create' => Pages\CreatePago::route('/create'),
            'edit' => Pages\EditPago::route('/{record}/edit'),
        ];
    }
}
