<?php

namespace App\Filament\App\Community\Resources\CommunityPlans;

use App\Filament\App\Community\Actions\GeneratePlanWorkOrdersAction;
use App\Filament\App\Community\Resources\CommunityPlans\RelationManagers\ItemsRelationManager;
use App\Filament\App\Community\Resources\CommunityPlans\RelationManagers\WorkOrdersRelationManager;
use App\Filament\App\Community\Resources\CommunityPlans\RelationManagers\TasksRelationManager;
use App\Models\CommunityPlan;
use BackedEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;
use Filament\Notifications\Notification;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Filters\TernaryFilter;

class CommunityPlanResource extends Resource
{
    protected static ?string $model = CommunityPlan::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $navigationLabel = 'Planes Mantenimiento';
    protected static ?string $pluralModelLabel = 'Planes Mantenimiento';

    protected static ?string $modelLabel = 'Planes';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';
    protected static ?string $navigationParentGroup = 'Mantenimiento';
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\ViewCommunityPlan::class,
            Pages\EditCommunityPlan::class,
            Pages\ManagePlanCatalogs::class,
            Pages\ManagePlanItems::class,
            Pages\ManagePlanWorkOrders::class,
            Pages\ManagePlanTasks::class,
            Pages\ManagePlanIncidents::class,
        ]);
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Mantenimiento';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Plan de mantenimiento')->schema([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Nombre')->required()->maxLength(255),
            TextInput::make('description')->label('Descripción')->maxLength(255)->columnSpanFull(),
            DatePicker::make('valid_from')->label('Válido desde')->required(), DatePicker::make('valid_until')->label('Válido hasta')->afterOrEqual('valid_from'), Select::make('status')->label('Estado')->options(['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo', 'replaced' => 'Sustituido'])->default('draft')->required()])->columns(2)]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Plan')->schema([TextEntry::make('community.name')->label('Comunidad'), TextEntry::make('status')->label('Estado')->badge(), TextEntry::make('valid_from')->label('Desde')->date(), TextEntry::make('valid_until')->label('Hasta')->date()->placeholder('Sin fin'), TextEntry::make('items_count')->label('Tareas planificadas'), TextEntry::make('active_items_count')->label('Activas')])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('valid_from', 'desc')->columns([TextColumn::make('community.name')->label('Comunidad')->searchable()->sortable(), TextColumn::make('name')->label('Nombre'), TextColumn::make('valid_from')->label('Desde')->date()->sortable(), TextColumn::make('valid_until')->label('Hasta')->date()->placeholder('Sin fin'), TextColumn::make('items_count')->label('Tareas')->counts('items'), TextColumn::make('status')->label('Estado')->badge()])->filters([SelectFilter::make('community')->relationship('community', 'name')->searchable()->preload(), SelectFilter::make('status')->options(['draft' => 'Borrador', 'active' => 'Activo', 'inactive' => 'Inactivo', 'replaced' => 'Sustituido'])])
        ->recordActions([
                GeneratePlanWorkOrdersAction::make(),
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])->headerActions([
            CreateAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('catalogs','community','items')->withCount(['catalogs','items', 'items as active_items_count' => fn (Builder $query) => $query->where('active', true)]);
    }

    public static function getRelations(): array
    {
        return [ItemsRelationManager::class, WorkOrdersRelationManager::class, TasksRelationManager::class];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCommunityPlans::route('/'), 'create' => Pages\CreateCommunityPlan::route('/create'), 'view' => Pages\ViewCommunityPlan::route('/{record}'),
            'edit' => Pages\EditCommunityPlan::route('/{record}/edit'),
            'orders' => Pages\ManagePlanWorkOrders::route('/{record}/orders'),
            'plan_items' => Pages\ManagePlanItems::route('/{record}/plan_items'),
                        'catalogs' => Pages\ManagePlanCatalogs::route('/{record}/catalogs'),


            'incidents' => Pages\ManagePlanIncidents::route('/{record}/incidents'), 
            'tasks' => Pages\ManagePlanTasks::route('/{record}/tasks'), ];

    }
}
