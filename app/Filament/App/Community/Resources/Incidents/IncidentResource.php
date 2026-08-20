<?php

namespace App\Filament\App\Community\Resources\Incidents;

use App\Actions\Community\ResolveIncident;
use App\Models\Incident;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Enums\IconSize;
use App\Enums\TablerIcon;
use App\Models\WorkCatalog;
use App\Models\WorkCategory;

class IncidentResource extends Resource
{
    protected static ?string $model = Incident::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedExclamationTriangle;
    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';
    protected static ?string $navigationParentGroup = 'Ordenes';
    protected static ?string $navigationLabel = 'Incidencias';

    protected static ?string $modelLabel = 'Incidencia';

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Propietarios';
    }

    public static function getNavigationSort(): ?int
    {
        return 4;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
            ->hiddenLabel()
            ->columnSpanFull()
            ->schema([
                Select::make('person_id')->label('Propietario')->relationship('person', 'display_name')->searchable()->preload(),
                Select::make('property_id')->label('Propiedad')->relationship('property', 'name')->searchable()->preload(), 
 
                Select::make('work_category_id')->label('Tipo de Sevicio')
                 ->options(function () {
                                        return WorkCategory::query()
                                        ->withCount('catalogItems')
                                        //->whereHas('catalogItems')
                                            ->orderBy('name')
                                            ->pluck('name', 'id');
                                    })
                ->searchable()->preload()->live()
                ->afterStateUpdated(fn (Set $set) => $set('work_catalog_id', null))->required(),
                Select::make('work_catalog_id')->label('Servicio en Plan')->relationship('workCatalog', 'title', modifyQueryUsing: fn (Builder $query, Get $get): Builder => $query->where('active', true)->when($get('work_category_id'), fn (Builder $serviceQuery, $categoryId): Builder => $serviceQuery->where('work_category_id', $categoryId)))->searchable()->preload(),

                            Section::make()
                                        ->columnSpanFull()
                            ->schema([
                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),
                Select::make('work_order_id')->label('Orden de trabajo')->relationship('workOrder', 'code')->searchable()->preload(),
                Select::make('work_order_task_id')->label('Tarea en orden')->relationship('workOrderTask', 'title')->searchable()->preload(),
                
                  ])->columns(3),
                TextInput::make('title')->label('Título')->required()->columnSpanFull(),
                Textarea::make('description')->label('Descripción')->required()->columnSpanFull(),
                Select::make('priority')->label('Prioridad')->options(self::priorities())->default('normal')->required(),
                Select::make('status')->label('Estado')->options(self::statuses())->default('open')->required(),
                Textarea::make('resolution_note')->label('Resolución')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Incidencia')->schema(
                [TextEntry::make('title')->label('Título'),
                    TextEntry::make('community.name')->label('Comunidad'),
                    TextEntry::make('workCategory.name')->label('Tipo de servicio')->badge(),
                    TextEntry::make('workCatalog.title')->label('Servicio')->placeholder('Sin servicio concreto'),
                    TextEntry::make('description')->label('Descripción')->columnSpanFull(),
                    TextEntry::make('priority')->label('Prioridad')->badge(),
                    TextEntry::make('status')->label('Estado')->badge(),
                    TextEntry::make('workOrder.code')->label('Orden')->placeholder('Sin orden'),
                    TextEntry::make('workOrderTask.title')->label('Tarea')->placeholder('Sin tarea'),
                    TextEntry::make('creator.name')->label('Reportada por')->placeholder('—'),
                    TextEntry::make('resolved_at')->label('Resuelta')->dateTime()->placeholder('Pendiente'),
                    TextEntry::make('resolution_note')->label('Resolución')->columnSpanFull()->placeholder('Pendiente')])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([TextColumn::make('title')->label('Incidencia')->searchable()->wrap(), 
        TextColumn::make('community.name')->label('Comunidad')->searchable(), TextColumn::make('workCategory.name')->label('Tipo')->badge(), 
        TextColumn::make('workCatalog.title')->label('Servicio')->placeholder('—')->toggleable(), 
        TextColumn::make('workOrder.code')->label('Orden')->placeholder('—'),
                        TextColumn::make('person.display_name')->label('Propietario')->searchable(), 
                        TextColumn::make('property.name')->label('Propiedad'),

        TextColumn::make('priority')->label('Prioridad')->badge(),
            TextColumn::make('status')->label('Estado')->badge()
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'open' => 'Abierta',
                    'in_progress' => 'Resolviendo',
                    'resolved' => 'Resuelta',
                    default => 'Sin estado',
                })
                ->color(fn (string $state): string => match ($state) {
                    'open' => 'warning',
                    'in_progress' => 'danger',
                    'resolved' => 'success',
                    default => 'gray',
                }),

            TextColumn::make('created_at')->label('Fecha')->dateTime()->sortable(), 
            TextColumn::make('photos_count')->label('Fotos')->counts('photos'),

            TextColumn::make('comments_count')->label('Comentarios')->counts('comments')
            ])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('work_category_id')->label('Tipo de servicio')->relationship('workCategory', 'name')->searchable()->preload(), SelectFilter::make('priority')->options(self::priorities()), SelectFilter::make('status')->options(self::statuses()), Filter::make('open')->label('Abiertas')->query(fn (Builder $query): Builder => $query->whereNotIn('status', ['resolved', 'closed'])), Filter::make('with_order')->label('Con orden')->query(fn (Builder $query): Builder => $query->whereNotNull('work_order_id'))])->recordActions([Action::make('resolve')->label('Resolver')->icon('heroicon-o-check-circle')->color('success')->visible(fn (Incident $record): bool => ! in_array($record->status, ['resolved', 'closed'], true))->requiresConfirmation()->schema([Textarea::make('note')->label('Nota de resolución')])->action(function (Incident $record, array $data): void {
                app(ResolveIncident::class)->handle($record, 'resolved', auth()->id(), $data['note'] ?? null);
                Notification::make()->title('Incidencia resuelta')->success()->send();
            })])
        ->recordActions([
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
        ->headerActions([
        CreateAction::make()
        ->iconButton()
        ->iconSize(IconSize::ExtraLarge)
        ->icon(TablerIcon::Plus)
        ->hiddenLabel()
        ->slideOver()
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['comments2','community', 'workCategory', 'workCatalog', 'workOrder', 'workOrderTask', 'creator', 'resolver'])->withCount(['comments2', 'photos']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\CommentsRelationManager::class, RelationManagers\PhotosRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListIncidents::route('/'), 
        //'create' => Pages\CreateIncident::route('/create'), 
        'view' => Pages\ViewIncident::route('/{record}'), 
        'edit' => Pages\EditIncident::route('/{record}/edit')
        ];
    }

    private static function priorities(): array
    {
        return ['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'];
    }

    private static function statuses(): array
    {
        return ['open' => 'Abierta', 'in_progress' => 'En curso', 'resolved' => 'Resuelta', 'closed' => 'Cerrada'];
    }
}
