<?php

namespace App\Filament\App\Community\Resources\Communities;

use App\Models\Community;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class CommunityResource extends Resource
{
    protected static ?string $model = Community::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?string $navigationLabel = 'Comunidades';

    protected static ?string $modelLabel = 'Comunidad';

    protected static ?string $pluralModelLabel = 'Comunidades';
    protected static ?string $navigationParentGroup = 'Mantenimiento';
    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static string|UnitEnum|null $navigationGroup = 'Mantenimiento';
    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
Pages\ViewCommunity::class,
Pages\ManageCommunityProperties::class,
            Pages\ManagePlanCatalogs::class,
            Pages\ManagePlans::class,
            Pages\CalendarCommunity::class,
            Pages\ManagePlanWorkOrders::class,
            Pages\ManagePlanIncidents::class,
Pages\ManageOwnerFees::class,

        ]);
    }
    /*public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'NOVA Community';
    }*/

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')->schema([
                TextInput::make('code')->label('Código')->required()->maxLength(50)->unique(ignoreRecord: true),
                TextInput::make('name')->label('Nombre')->required()->maxLength(255),
                TextInput::make('address')->label('Dirección')->maxLength(255)->columnSpanFull(),
                TextInput::make('contact_name')->label('Contacto')->maxLength(255),
                TextInput::make('contact_phone')->label('Teléfono')->tel()->maxLength(50),
                Select::make('status')->label('Estado')->options(['active' => 'Activa', 'inactive' => 'Inactiva'])->default('active')->required(),
                Textarea::make('notes')->label('Notas')->columnSpanFull(),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Comunidad')->schema([
                TextEntry::make('code')->label('Código'), TextEntry::make('name')->label('Nombre'), TextEntry::make('address')->label('Dirección'), TextEntry::make('status')->label('Estado')->badge(), TextEntry::make('contact_name')->label('Contacto'), TextEntry::make('contact_phone')->label('Teléfono'),
            ])->columns(2),
            Section::make('Operaciones')->schema([
                TextEntry::make('active_plans_count')->label('Planes activos'),
                TextEntry::make('work_orders_count')->label('Órdenes'), 
                TextEntry::make('pending_orders_count')->label('Pendientes')->badge()->color('warning'),
                TextEntry::make('open_incidents_count')->label('Incidencias abiertas')->badge()->color(fn (int $state): string => $state ? 'danger' : 'success'), 

            ])->columns(4),
            Section::make('Actividad reciente')->schema([
                TextEntry::make('workOrders.code')->label('Órdenes recientes')->badge()->limitList(6), 
                TextEntry::make('incidents.title')->label('Incidencias recientes')->badge()->limitList(6),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('name')->columns([
            TextColumn::make('code')->label('Código')->searchable()->sortable(), TextColumn::make('name')->label('Comunidad')->searchable()->sortable(), 
            TextColumn::make('address')->label('Dirección')->searchable()->toggleable(), 
                        TextColumn::make('plans_count')->label('Planes')->counts('plans'), 
            TextColumn::make('properties_count')->label('Propiedades')->counts('properties'), 
            TextColumn::make('work_orders_count')->label('Órdenes')->counts('workOrders'), 
            TextColumn::make('open_incidents_count')->label('Incidencias')->badge()->color(fn (int $state): string => $state ? 'danger' : 'success'), 
            TextColumn::make('status')->label('Estado')->badge(),
        ])->filters([SelectFilter::make('status')->options(['active' => 'Activa', 'inactive' => 'Inactiva'])]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['workOrders','properties', 'workOrders as pending_orders_count' => fn (Builder $query) => $query->whereIn('status', ['pending', 'in_progress']), 'incidents as open_incidents_count' => fn (Builder $query) => $query->whereNotIn('status', ['resolved', 'closed']), 'plans as active_plans_count' => fn (Builder $query) => $query->where('status', 'active')])->with(['workOrders' => fn ($query) => $query->latest('work_date')->limit(6), 'incidents' => fn ($query) => $query->latest()->limit(6)]);
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListCommunities::route('/'), 'create' => Pages\CreateCommunity::route('/create'), 'view' => Pages\ViewCommunity::route('/{record}'), 'calendar' => Pages\CalendarCommunity::route('/{record}/calendar'), 'edit' => Pages\EditCommunity::route('/{record}/edit'),
                        'catalogs' => Pages\ManagePlanCatalogs::route('/{record}/catalogs'),
        'plans' => Pages\ManagePlans::route('/{record}/plans'),
        'orders' => Pages\ManagePlanWorkOrders::route('/{record}/orders'),
            'incidents' => Pages\ManagePlanIncidents::route('/{record}/incidents'), 
            'properties' => Pages\ManageCommunityProperties::route('/{record}/properties'), 
            'fees' => Pages\ManageOwnerFees::route('/{record}/fees'), 

];
    
        }

    public static function getRelations(): array
    {
        return [RelationManagers\PlansRelationManager::class, RelationManagers\WorkOrdersRelationManager::class, RelationManagers\TasksRelationManager::class, RelationManagers\IncidentsRelationManager::class, RelationManagers\OwnersRelationManager::class, RelationManagers\PropertiesRelationManager::class, RelationManagers\DepartmentsRelationManager::class];
    }
}
