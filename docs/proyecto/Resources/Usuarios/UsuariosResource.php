<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Usuarios;

// use App\AdminDashboardSidebarSorting;

// use App\Enums\UsuarioTipo;
use App\Filament\App\Resources\Usuarios\Pages\EditUsuario;
use App\Filament\App\Resources\Usuarios\Pages\ListUsuarios;
use App\Filament\App\Resources\Usuarios\Pages\ViewUsuario;
use App\Filament\App\Resources\Usuarios\Widgets\UsuarioStats;
use App\Filament\App\Resources\Municipios\Pages\ManageMunicipios;
use App\Filament\Support\baseresource;
use App\Models\BookingDepartment;
use App\Models\Taxi\EstadosUsuario;
use App\Models\Taxi\Municipio;
use App\Models\Taxi\Turnos;
use App\Models\Taxi\Usuario;
use App\Models\Role;
use App\Models\User;

use App\Models\UserType;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use UnitEnum;
use Filament\Support\Icons\Heroicon;
use BackedEnum;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Components\Tables\InlineEditColumn;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\Tables\ExportAction as ExcelExportAction;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction as ExcelExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
/*use App\Filament\App\Clusters\Taxistas\Usuarios\Pages\CitasUsuarios;
use App\Filament\App\Clusters\Taxistas\Usuarios\Pages\ListActivitiesUser;
use App\Filament\App\Clusters\Taxistas\Usuarios\Pages\AttendancesUsuarios;*/

/* use App\Filament\Widgets\LocationMapWidget; */
// use App\Models\Taxi\Blog\Category;
// use App\Models\Taxi\Blog\Post;

// use App\Models\Taxi\Empleado;
// use App\Models\Taxi\Location;

// use App\Models\Taxi\TipoDoc;
// use App\Models\Taxi\Marcador;
// use App\Models\Taxi\TipoUsuario;

// use Guava\FilamentModalRelationManagers\Actions\Action\RelationManagerAction;
// use STS\FilamentImpersonate\Tables\Actions\Impersonate;
use Filament\Resources\Resource;

final class UsuariosResource extends Resource
{
    // use HasPageSidebar; // use this trait to activate the Sidebar

    protected static ?string $model = User::class;

    protected static string $userIdColumn = 'id';

    protected static array $defaultCenter = ['lat' => 28.921144, 'lng' => -13.6413440];
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;

    protected static array $defaultBoundaries = ['north' => 0 + 0.1, 'south' => 0 - 0.1, 'east' => 0 + 0.1, 'west' => 0 - 0.1];

    //    protected static string | BackedEnum | null $navigationIcon  = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Usuarios';

    protected static \UnitEnum|string|null $navigationGroup = 'Servicios de Empleados';

    protected static ?int $navigationSort = -3;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;
    protected static bool $isScopedToTenant = false; // Added this line

    public static function getTableDefaultAction(): ?string
    {
        return 'edit';   // ← Acción por defecto al hacer clic en una fila
    }

    public static function getTableExports(): array
    {
        return [
            ExcelExport::make('usuarios')
                ->fromTable()
                ->askForFilename()
                ->askForWriterType(
                    default: Excel::XLSX,
                    options: [
                        Excel::XLSX => 'XLSX',
                        Excel::DOMPDF => 'PDF',
                    ],
                    label: 'Formato'
                )
                ->withFilename(fn (): string => 'usuarios-' . now()->format('Y-m-d-His'))
                ->withWriterType(fn (?string $writerType): string => $writerType ?: Excel::XLSX),
        ];
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
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('name')->label('Nombre')
                                    ->afterStateHydrated(function ($record, $set, $operation) {
                                        if ($operation === 'edit') {
                                            $set('name', $record->name);
                                        }
                                    }),
                                TextInput::make('nif')->columnSpan('xs')->label('NIF'),
                                TextInput::make('licencia')->columnSpan('xs'),
                                Select::make('role')
                                    ->label('Role')
                                   ->options([
                                'super' => 'super',
                                'admin' => 'admin',
                                'empleado'   => 'empleado',
                                'conductor'   => 'conductor',
                                'taxista'   => 'taxista',
                            ])
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live(),
                                Select::make('type_id')
                                    ->label('Tipo')
                                    ->relationship('type', 'name')
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
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Select::make('booking_department_id')
                                    ->label('Departamento')
                                    ->options(function () {
                                        return BookingDepartment::query()
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                                    ->nullable()
                                    ->preload()
                                    ->columnSpan('md'),
                                Select::make('shift_preference')
                                    ->label('Turno')
                                    ->options([
                                'M'   => 'Mañana',
                                'T'   => 'Tarde',
                                'N'   => 'Noche',
                                'any' => 'Cualquiera',
                            ])
                                    ->preload()
                                    ->columnSpan('md'),
                                TextInput::make('password'),
                                TextInput::make('email'),
                                TextInput::make('phone')->label('Telefono'),
                                Toggle::make('status')
                                    ->label('Estado'),
                            ]),

                        Tab::make('Dirección')
                            ->columns(2)
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                TextInput::make('address')
                                    ->label('Dirección')->columns(1),
                                Select::make('municipio_id')
                                    ->label('Municipio')
                                    ->relationship('municipio', 'nombre')
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('nombre')
                                            ->required()
                                            ->maxLength(255),
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
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                )
            ->defaultSort(
                fn(Builder $query): Builder => $query
                    ->orderBy('municipio_id')
                    ->orderByRaw("CASE WHEN REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+') IS NULL THEN 1 ELSE 0 END")
                    ->orderByRaw("CAST(COALESCE(REGEXP_SUBSTR(COALESCE(licencia, ''), '[0-9]+'), '0') AS UNSIGNED)")
                    ->orderBy('name')
            )
            ->columns([
                    TextColumn::make('id')
                        ->label('ID')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),

                    TextColumn::make('name')
                        ->searchable()
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->extraAttributes(['style' => 'font-weight: bold']),

                    //                Tables\Columns\TextColumn::make('lat'),
                    //                Tables\Columns\TextColumn::make('lng'),
                    BadgeColumn::make('type.name')
                        ->label('Tipo')
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->color(
                            fn($record): string => $record->type?->color ?? 'gray',
                        )
                        ->formatStateUsing(
                            function ($record) {
                                $tax = [];
                                $getTaxes = UserType::whereIn('id', $record->tipo()->pluck('id'))->get();
                                foreach ($getTaxes as $taxes) {
                                    $tax[] = $taxes->value;
                                }

                                return !empty($tax) ? implode(', ', $tax) : '';
                            })
                        ->formatStateUsing(fn(string $state): string => mb_strtoupper($state)) // Convierte a mayúsculas
                        ->extraAttributes(function ($record) {
                            $color = $record->type?->color;

                            return $color ? ['style' => "color: {$color}; font-weight: 600;", 'class' => "fi-color fi-color-{$color} fi-ta-text-has-badges fi-text-color-900 dark:fi-text-color-200"] : [];
                        })
                        ->searchable()
                        ->sortable(),
                    TextColumn::make('nif')
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
                    TextColumn::make('role')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),
                    TextColumn::make('address')
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

                    TextColumn::make('phone')
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
               IconSelectColumn::make('status')
                    ->label('Estado')
                    ->sortable()
                    ->icons([
                            true => 'heroicon-o-check-circle',
                            false => 'heroicon-o-x-circle',
                        ]
                    )
                    ->colors([
                        true => 'success',
                        false => 'gray',
                    ])
                    ->default(false)
                                        ->toggleable(isToggledHiddenByDefault: false),

                IconSelectColumn::make('is_featured')
                    ->label('Destacado')
                    ->icons([
                            true => 'heroicon-s-star',
                            false => 'heroicon-o-x-circle',
                        ]
                    )
                    ->colors([
                        true => 'warning',
                        false => 'gray',
                    ])
                    ->default(false)
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('is_online')
                    ->label('Online')
                    ->badge()
                    ->formatStateUsing(fn(bool $state): string => $state ? 'Online' : 'Offline')
                    ->color(fn(bool $state): string => $state ? 'success' : 'gray')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                              TextColumn::make('taxista.name')
                        ->label('Taxista')
                        ->sortable()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->searchable(),

                    IconColumn::make('is_encargado')
                        ->label('Encargado')
                        ->sortable()
                        ->boolean()
                        ->toggleable(isToggledHiddenByDefault: false)
                        ->trueIcon('heroicon-m-check-circle')->trueColor('success')
                        ->falseIcon('heroicon-m-x-circle')->falseColor('danger'),

                    /*Tables\Columns\TagsColumn::make('departamentos')
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
                        ->trueIcon('heroicon-m-check-circle')->trueColor('success')
                        ->falseIcon('heroicon-m-x-circle')->falseColor('danger'),

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
                    CreateAction::make()
                        ->label('Nuevo Taxista')
                        ->iconbutton()
                        ->icon('heroicon-m-plus-circle')
                        ->color('primary'),
                    ExcelExportAction::make()
                        ->label('Exportar')
                                                ->iconbutton()

                        ->icon('heroicon-o-arrow-down-tray')
                        ->exports(static::getTableExports()),
                ])
                ->filters([
                        SelectFilter::make('status')
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
                                SelectFilter::make('type_id')
                    ->label('Tipo')
                    ->relationship('type', 'label')
                    ->searchable()
                    ->preload(),
                TernaryFilter::make('has_taxista')
                    ->label('Taxista asociado')
                    ->trueLabel('Con Taxista')
                    ->falseLabel('Sin Taxista')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('taxista_id')
                            ->where('taxista_id', '!=', ''),
                        false: fn (Builder $query): Builder => $query
                            ->where(function (Builder $nestedQuery): Builder {
                                return $nestedQuery
                                    ->whereNull('taxista_id')
                                    ->orWhere('taxista_id', '');
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                TernaryFilter::make('is_featured')
                    ->label('Destacado')
                    ->trueLabel('Destacados')
                    ->falseLabel('No destacados')
                    ->placeholder('Todos'),
                        TernaryFilter::make('is_encargado')
                            ->label('Encargado')
                            ->searchable()
                            ->preload(),
                        SelectFilter::make('role')
                            ->label('Role')
                              ->options([
                                'super' => 'super',
                                'admin' => 'admin',
                                'empleado'   => 'empleado',
                                'conductor'   => 'conductor',
                                'taxista'   => 'taxista',
                            ])
                            ->preload(),
                            TernaryFilter::make('has_licencia')
                    ->label('Licencia')
                    ->trueLabel('Con licencia')
                    ->falseLabel('Sin licencia')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query
                            ->whereNotNull('licencia')
                            ->where('licencia', '!=', ''),
                        false: fn (Builder $query): Builder => $query
                            ->where(function (Builder $nestedQuery): Builder {
                                return $nestedQuery
                                    ->whereNull('licencia')
                                    ->orWhere('licencia', '');
                            }),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                        TernaryFilter::make('bloqueado'),

                    ]
                )
                ->filtersLayout(FiltersLayout::Modal)
                ->filtersFormSchema(fn(array $filters): array => [
                    Section::make()
                        ->schema([
                            $filters['municipio_id'],
                            $filters['is_encargado'],
                            $filters['has_taxista'],
                            $filters['role'],
                            $filters['status'],
                            $filters['has_licencia'],
                            $filters['type_id'],
                            $filters['is_featured'],
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


                        /*Action::make('Cita')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [UsuarioTipo::EMPLOYEE, UsuarioTipo::ADMIN, UsuarioTipo::DEPARTAMENTO]))
                        ->icon('heroicon-m-banknotes')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::PAID;
                            $record->save();
                        }),*/
                        /*Action::make('Mark as Sent')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => $record->invoice_status === InvoiceStatus::OPEN)
                        ->icon('heroicon-o-envelope')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::SENT;
                            $record->save();
                        }),
                    Action::make('Refund')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::PAID, InvoiceStatus::PARTIALLY_PAID]))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::REFUNDED;
                            $record->save();
                        }),
                    Action::make('Mark as Cancelled')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::OPEN, InvoiceStatus::SENT]))
                        ->icon('heroicon-o-arrow-uturn-left')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::REFUNDED;
                            $record->save();
                        }),
                   Action::make('Mark as Re-sent')
                        ->hiddenLabel()
                        ->button()
                        ->visible(fn($record) => in_array($record->invoice_status, [InvoiceStatus::CANCELLED, InvoiceStatus::REJECTED]))
                        ->icon('heroicon-m-arrow-right-end-on-rectangle')
                        ->requiresConfirmation()
                        ->action(function (Invoice $record) {
                            $record->invoice_status = InvoiceStatus::SENT;
                            $record->save();
                        }),*/

                        ViewAction::make()
                            ->label(''),
                        // RadiusAction::make('radius'),
                        // ReplicateAction::make(),
                        /*Action::make('tipo_id')
                        ->label('Tipo Hotel')
                        ->icon('heroicon-o-check-circle')
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
                        ->icon('heroicon-m-folder')
                        ->color('gray')
                        ->url(fn($record) => route(ManageMunicipios::getRouteName('app'), [
                            'tableFilters[parent][id]' => data_get($record, 'municipio_id'),
                        ]))
                        ->iconButton(),
                    */
                    ])
                        ->icon('heroicon-m-ellipsis-horizontal'),
                ])
                ->bulkActions([
                    BulkActionGroup::make([
                        ExcelExportBulkAction::make()
                            ->label('Exportar seleccionados')
                            ->exports(static::getTableExports()),
                        DeleteBulkAction::make(),
                        /*BulkAction::make('Cambiar TIPO')
        ->icon('heroicon-m-pencil-square')
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
                            ->icon('heroicon-m-pencil-square')
                            ->form([
                                Select::make('municipio_id')
                                    ->label('Municipios')
                                    ->default(1)
                                    ->relationship('municipio', 'nombre')
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['municipio_id' => $data['municipio_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),
                        BulkAction::make('Cambiar Tipo')
                            ->icon('heroicon-m-pencil-square')
                            ->form([
                                Select::make('type_id')
                                    ->label('Tipo')
                                    ->default(1)
                                    ->relationship('tipo', 'name')
                                    ->required(),
                            ])
                            ->action(function (Collection $records, array $data): void {
                                $records->each->update(['type_id' => $data['type_id']]);
                            })
                            ->deselectRecordsAfterCompletion(),

                        BulkAction::make('featureSelected')
                            ->label('Feature Selected')
                            ->icon('heroicon-o-star')
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
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(fn(Usuario $records) => $records->each->update(['estado_id' => 1])),
                        BulkAction::make('deactivate')
                            ->label('Desactivar')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(fn(Usuario $records) => $records->each->update(['estado_id' => 0])),
                    ]),
                ])
                ->emptyStateActions([
                    // CreateAction::make(),
                ])
                            ->paginated([10, 25, 50, 100, 'all']);
    }

    public static function getWidgets(): array
    {
        return [
            UsuarioStats::class,
        ];
    }

    public static function getRelations(): array
    {
        return [

            //RelationManagers\DispositivosRelationManager::class,
            //RelationManagers\PdfsRelationManager::class,
             
            RelationManagers\PagosRelationManager::class,

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
            //'view' => ViewUsuario::route('/{record}/view'),
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
