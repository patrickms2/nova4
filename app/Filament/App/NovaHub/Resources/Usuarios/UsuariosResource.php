<?php

declare(strict_types=1);

namespace App\Filament\App\NovaHub\Resources\Usuarios;

use Filament\Support\Icons\Heroicon;

// use App\AdminDashboardSidebarSorting;

// use App\Enums\UsuarioTipo;
use App\Enums\UsuarioTipo;
use App\Filament\App\NovaHub\Resources\Municipios\Pages\ManageMunicipios;
use App\Filament\App\NovaHub\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\App\NovaHub\Resources\Usuarios\Pages\ListUsuarios;

/*use App\Filament\App\NovaHub\Resources\Usuarios\Pages\CitasUsuarios;
use App\Filament\App\NovaHub\Resources\Usuarios\Pages\ListActivitiesUser;
use App\Filament\App\NovaHub\Resources\Usuarios\Pages\AttendancesUsuarios;*/

use App\Filament\App\NovaHub\Resources\Usuarios\Pages\ViewUsuario;
use App\Filament\Components\Tables\InlineEditColumn;
use App\Filament\Support\baseresource;
use App\Filament\Tables\Actions\ImpersonateTableAction;
use App\Models\Taxi\Departamento;

/* use App\Filament\Widgets\LocationMapWidget; */
// use App\Models\Taxi\Blog\Category;
// use App\Models\Taxi\Blog\Post;
use App\Models\Taxi\EstadosUsuario;

// use App\Models\Taxi\Empleado;
// use App\Models\Taxi\Location;
use App\Models\Taxi\Municipio;

// use App\Models\Taxi\TipoDoc;
// use App\Models\Taxi\Marcador;
// use App\Models\Taxi\TipoUsuario;
use App\Models\Taxi\TipoUsuario;
use App\Models\Taxi\Turnos;
use App\Models\Taxi\Usuario;
use BackedEnum;
use Cheesegrits\FilamentGoogleMaps\Actions\RadiusAction;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema as Form;
use Filament\Support\Enums\Width;
use Filament\Tables;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;

// use Guava\FilamentModalRelationManagers\Actions\Action\RelationManagerAction;
// use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;

final class UsuariosResource extends baseresource
{
    // use HasPageSidebar; // use this trait to activate the Sidebar

    protected static ?string $model = Usuario::class;


    protected static array $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];

    protected static array $defaultBoundaries = ['north' => 0 + 0.1, 'south' => 0 - 0.1, 'east' => 0 + 0.1, 'west' => 0 - 0.1];

    //    protected static string | BackedEnum | null $navigationIcon  = Heroicon::OutlinedRectangleStack;
    protected static ?string $navigationLabel = 'Usuarios';

    protected static string | UnitEnum | null $navigationGroup = 'Taxis';

    protected static ?int $navigationSort = -3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';   // ← Acción por defecto al hacer clic en una fila
    }

    public static function form(Form $form): Form
    {
        $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];
        $defaultBoundaries = ['north' => $defaultCenter['lat'] + 0.1, 'south' => $defaultCenter['lat'] - 0.1, 'east' => $defaultCenter['lng'] + 0.1, 'west' => $defaultCenter['lng'] - 0.1];

        return $form
            ->schema([
                Tabs::make('Datos de Usuario')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Personales')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                TextInput::make('nombre')->label('Nombre')
                                    ->afterStateHydrated(function ($record, $set, $operation) {
                                        if ($operation === 'edit') {
                                            $set('nombre', $record->nombre);
                                        }
                                    }),
                                TextInput::make('cif')->columnSpan('xs')->label('NIF'),
                                TextInput::make('licencia')->columnSpan('xs'),
                                Select::make('tipo_id')
                                    ->label('Tipo')
                                    ->relationship('tipo', 'nombre')->searchable()
                                    ->required()
                                    ->default(3)
                                    ->live()
                                    ->createOptionForm([
                                        TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Hidden::make('estado')
                                            ->default(1),
                                    ])
                                    ->createOptionAction(function (Action $action) {
                                        return $action
                                            ->modalHeading('Create Tipo')
                                            ->modalSubmitActionLabel('Create Tipo')
                                            ->modalWidth('lg');

                                    }),

                            ]),

                        Tab::make('Trabajo')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                Select::make('departamentos')
                                    ->label('Departamento')
                                    ->options(function () {
                                        return Departamento::query()
                                            ->orderBy('nombre')
                                            ->pluck('nombre', 'id');
                                    })
                                    ->searchable()
                                    ->multiple()
                                    ->nullable()
                                    ->preload()
                                    ->columnSpan('md'),
                                Select::make('turnos')
                                    ->label('Turno')
                                    ->options(function () {
                                        return Turnos::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })->searchable()
                                    ->multiple()
                                    ->nullable()
                                    ->preload()
                                    ->columnSpan('md'),
                                TextInput::make('usuario'),
                                TextInput::make('password'),
                                TextInput::make('email'),
                                TextInput::make('tel_fijo')->label('Telefono'),
                                Toggle::make('estado_id')
                                    ->label('Estado'),
                                Toggle::make('bloqueado')
                                    ->label('Bloqueado'),
                            ]),

                        Tab::make('Dirección')
                            ->columns(2)
                            ->icon(Heroicon::OutlinedInformationCircle)
                            ->schema([
                                TextInput::make('direccion')
                                    ->label('Dirección')->columns(1),
                                Select::make('municipio_id')
                                    ->label('Municipio')
                                    ->relationship('municipio', 'nombre')->searchable()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255),
                                        Hidden::make('estado')
                                            ->default(1),
                                    ])
                                    ->createOptionAction(function (Action $action) {
                                        return $action
                                            ->modalHeading('Create Municipio')
                                            ->modalSubmitActionLabel('Create Municipio')
                                            ->modalWidth('lg');
                                    }),

                            ]),
                    ]),

            ]);

    }

    public static function table(Table $table): Table
    {
        // Configuración de la visualización de la tabla
        return
            $table
                ->columns([
                    TextColumn::make('id')
                        ->label('ID')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),

                    TextColumn::make('nombre')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->extraAttributes(['style' => 'font-weight: bold']),

                    //                Tables\Columns\TextColumn::make('lat'),
                    //                Tables\Columns\TextColumn::make('lng'),
                    BadgeColumn::make('tipo.nombre')
                        ->label('Tipo')
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->color(
                            fn($record): string => $record->tipo?->color ?? 'gray',
                        )
                        ->formatStateUsing(
                            function ($record) {
                                $tax = [];
                                $getTaxes = TipoUsuario::whereIn('id', $record->tipo()->pluck('id'))->get();
                                foreach ($getTaxes as $taxes) {
                                    $tax[] = $taxes->value;
                                }

                                return !empty($tax) ? implode(', ', $tax) : '';
                            })
                        ->formatStateUsing(fn(string $state): string => mb_strtoupper($state)) // Convierte a mayúsculas
                        ->extraAttributes(function ($record) {
                            $color = $record->tipo?->color;

                            return $color ? ['style' => "color: {$color}; font-weight: 600;", 'class' => "fi-color fi-color-{$color} fi-ta-text-has-badges fi-text-color-900 dark:fi-text-color-200"] : [];
                        })
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('cif')
                        ->label('NIF')
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('licencia')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),

                    TextColumn::make('municipio.nombre')
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->badge()
                        ->color(
                            fn($record): string => $record->municipio?->color ?? 'gray',
                        )
                        ->formatStateUsing(fn(string $state): string => mb_strtoupper($state)) // Convierte a mayúsculas
                        ->extraAttributes(function ($record) {
                            $color = $record->municipio?->color;

                            return $color ? ['style' => "color: {$color}; font-weight: 600;", 'class' => "fi-color fi-color-{$color} fi-ta-text-has-badges fi-text-color-900 dark:fi-text-color-200"] : [];
                        })
                        ->sortable()
                        ->searchable(),

                    TextColumn::make('direccion')
                        ->label('Dirección')
                        ->sortable()
                        ->limit(15)
                        ->wrapHeader()
                        ->extraAttributes(['style' => 'font-weight: 200'])
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->size('xs')
                        ->searchable(),

                    TextColumn::make('email')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->limit(15)
                        ->size('xs')
                        ->searchable(),

                    TextColumn::make('tel_fijo')
                        ->label('Tel.')
                        ->sortable()
                        ->size('xs')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),

                    TextColumn::make('usuario')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),

                    TextColumn::make('password')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->searchable(),

                    Tables\Columns\TagsColumn::make('departamentos')
                        ->label('Departamentos')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable()
                        ->extraAttributes(['style' => 'font-weight: bold']),

                    Tables\Columns\TagsColumn::make('turnos')
                        ->label('Turnos')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable()
                        ->extraAttributes(['style' => 'font-weight: bold']),

                    IconColumn::make('is_encargado')
                        ->label('Encargado')
                        ->sortable()
                        ->boolean()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->trueIcon(Heroicon::CheckCircle)->trueColor('success')
                        ->falseIcon(Heroicon::XCircle)->falseColor('danger'),

                    BadgeColumn::make('estado.nombre')
                        ->label('Estado')
                        ->toggleable(isToggledHiddenByDefault: true)
                        ->sortable()
                        ->formatStateUsing(fn(string $state): string => mb_strtoupper($state))
                        ->extraAttributes(['style' => 'font-weight: bold'])
                        ->colors([
                            'danger' => fn($record) => $record->estado_id === 4,
                            'warning' => fn($record) => $record->estado_id === 3,
                            'success' => fn($record) => $record->estado_id === 1,
                            'info' => fn($record) => $record->estado_id === 2,
                            'gray' => fn($record) => $record->estado_id === 5,
                        ]),
                    /*InlineEditColumn::make('bloqueado')->type('boolean')
                    ->label('Bloqueado')
                    ,
                Tables\Columns\TextColumn::make('lat')
                    ->searchable(),
                Tables\Columns\TextColumn::make('lng')*/
                    //                Tables\Columns\TextColumn::make('formatted_address'),
                    //                MapColumn::make('location'),
                    //                Tables\Columns\TextColumn::make('created_at')
                    //                    ->dateTime(),
                    //                Tables\Columns\TextColumn::make('updated_at')
                    //                    ->dateTime(),
                ])
                ->headerActions([
                    // CreateAction::make(),
                ])
                ->filters([
                        SelectFilter::make('estado_id')
                            ->label('Estado')
                            ->options(function () {
                                return EstadosUsuario::query()
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id');
                            })
                            ->searchable()
                            ->preload(),

                        SelectFilter::make('municipio_id')
                            ->label('Municipio')
                            ->options(function () {
                                return Municipio::query()
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id');
                            })
                            ->searchable()
                            ->preload(),
                        TernaryFilter::make('is_encargado')
                            ->label('Encargado')
                            ->searchable()
                            ->preload(),
                        SelectFilter::make('tipo_id')
                            ->label('Tipo Usuario')
                            ->multiple()
                            ->options(function () {
                                return TipoUsuario::query()
                                    ->orderBy('nombre')
                                    ->pluck('nombre', 'id');
                            })
                            ->searchable()
                            ->preload(),
                        TernaryFilter::make('bloqueado'),

                    ]
                )
                ->filtersLayout(FiltersLayout::Modal)
                ->filtersFormSchema(fn(array $filters): array => [
                    Section::make()
                        ->schema([
                            $filters['municipio_id'],
                            $filters['is_encargado'],
                            $filters['tipo_id'],
                            $filters['estado_id'],
                            $filters['bloqueado'],
                        ])
                        ->columns(3)
                        ->columnSpanFull(),
                ])
                ->filtersFormWidth(Width::ThreeExtraLarge)
                ->recordActions([
                    EditAction::make('edit')
                        ->label('Editar'),
                    DeleteAction::make('delete'),

                    ActionGroup::make([

                        ImpersonateTableAction::make()
                            ->iconButton(),

                        /*Action::make('Cita')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [UsuarioTipo::EMPLOYEE, UsuarioTipo::ADMIN, UsuarioTipo::DEPARTAMENTO]))
                        ->icon(Heroicon::Banknotes)
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::PAID;
                            $record->save();
                        }),*/
                        /*Action::make('Mark as Sent')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => $record->invoice_status === InvoiceStatus::OPEN)
                        ->icon(Heroicon::OutlinedEnvelope)
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::SENT;
                            $record->save();
                        }),
                    Action::make('Refund')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID]))
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::REFUNDED;
                            $record->save();
                        }),
                    Action::make('Mark as Cancelled')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::OPEN, InvoiceStatus::SENT]))
                        ->icon(Heroicon::OutlinedArrowUturnLeft)
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::REFUNDED;
                            $record->save();
                        }),
                   Action::make('Mark as Re-sent')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::CANCELLED, InvoiceStatus::REJECTED]))
                        ->icon(Heroicon::ArrowRightEndOnRectangle)
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::SENT;
                            $record->save();
                        }),*/

                        ViewAction::make()
                            ->iconButton(),
                        // RadiusAction::make('radius'),
                        // ReplicateAction::make(),
                        /*Action::make('tipo_id')
                        ->label('Tipo Hotel')
                        ->icon(Heroicon::OutlinedCheckCircle)
                        ->color('success')
                        ->action(function (\App\Models\Taxi\Usuario $record) {
                            $record->update([
                                'tipo_id' => 2,
                            ]);

                            Notification::make()
                                ->title('Post published successfully')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation()
                        ->visible(),*/

                        /*Tables\Actions\Action::make('Abrir Municipios')
                        ->icon(Heroicon::Folder)
                        ->color('gray')
                        ->url(fn($record) => route(ManageMunicipios::getRouteName('app'), [
                            'tableFilters[parent][id]' => data_get($record, 'municipio_id'),
                        ]))
                        ->iconButton(),
                    */
                    ])
                        ->icon(Heroicon::EllipsisHorizontal),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                        /*BulkAction::make('Cambiar TIPO')
        ->icon(Heroicon::PencilSquare)
        ->form([
            Forms\Components\Select::make('tipo_id')
                ->label('Tipos')
                ->default(2)
                ->options(function () {
                    // Exclude the current category if editing
                    $query = TipoUsuario::query()->where('estado', '=', 1);
                    return $query->pluck('nombre', 'id');
                })
                ->required(),
        ])
        ->action(function (Collection $records, array $data): void {
            $records->each->update(['tipo_id' => $data['tipo_id']]);
        })
        ->deselectRecordsAfterCompletion(),*/

                        BulkAction::make('Cambiar MUNICIPIO')
                            ->icon(Heroicon::PencilSquare)
                            ->schema([
                                Select::make('municipio_id')
                                    ->label('Municipios')
                                    ->default(1)
                                    ->relationship('municipio', 'nombre')->searchable()
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['municipio_id' => $data['municipio_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),
                        BulkAction::make('Cambiar Tipo')
                            ->icon(Heroicon::PencilSquare)
                            ->schema([
                                Select::make('tipo_id')
                                    ->label('Tipo')
                                    ->default(1)
                                    ->relationship('tipo', 'nombre')->searchable()
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['tipo_id' => $data['tipo_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),

                        BulkAction::make('featureSelected')
                            ->label('Feature Selected')
                            ->icon(Heroicon::OutlinedStar)
                            ->color('warning')
                            ->action(function ($records): void {
                                foreach ($records as $record) {
                                    $record->update(['is_featured' => true]);
                                }

                                Notification::make()
                                    ->title('Selected posts featured successfully')
                                    ->success()
                                    ->send();
                            })
                            ->requiresConfirmation()
                            ->visible(),
                        BulkAction::make('activate')
                            ->label('Activar')
                            ->icon(Heroicon::CheckCircle)
                            ->requiresConfirmation()
                            ->action(fn(Usuario $records) => $records->each->update(['estado_id' => 1])),
                        BulkAction::make('deactivate')
                            ->label('Desactivar')
                            ->icon(Heroicon::XCircle)
                            ->requiresConfirmation()
                            ->action(fn(Usuario $records) => $records->each->update(['estado_id' => 0])),
                    ]),
                ])
                ->emptyStateActions([
                    // CreateAction::make(),
                ]);
    }

    public static function getWidgets(): array
    {
        return [
            // LocationMapWidget::class,
            // LocationStatsOverview::class,
        ];
    }

    public static function getRelations(): array
    {
        return [

            RelationManagers\DispositivosRelationManager::class,
            RelationManagers\PdfsRelationManager::class,

        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            // ViewUsuario::class,
            // EditUsuario::class,
            // ListUsuarios::class,
            // CitasUsuarios::class,
            // ListActivitiesUser::class,

        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsuarios::route('/'),
            'view' => ViewUsuario::route('/{record}/view'),
            // 'create' => Pages\CreateUsuario::route('/create'),
            'edit' => EditUsuario::route('/{record}/edit'),

        ];
    }

    protected function getDefaultTableAction(): ?string
    {
        return 'edit';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            // LocationMapTableWidget::class,
            // LocationMapWidget::class,
            // LocationStatsOverview::class,
        ];
    }
}
