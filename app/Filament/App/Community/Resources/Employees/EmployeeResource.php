<?php

namespace App\Filament\App\Community\Resources\Employees;

use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'Empleados';

    protected static ?string $modelLabel = 'Empleado';

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';
    protected static ?string $navigationParentGroup = 'Empleados';
    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([Pages\ViewEmployee::class, 
        Pages\ManageEmployeeDepartments::class, 
        Pages\ManageEmployeeShifts::class, 
        Pages\ManageEmployeeAttendances::class,
        //Pages\ManageEmployeePlans::class,
        Pages\ManageEmployeeOrders::class,
        Pages\ManageEmployeeTasks::class,
        Pages\ManageEmployeeIncidents::class,

        ]);
    }

    public static function form(Schema $s): Schema
    {
        return $s->components([Section::make('Empleado')->schema([TextInput::make('employee_code')->label('Código')->required()->unique(ignoreRecord: true), TextInput::make('name')->label('Nombre')->required(), TextInput::make('position')->label('Puesto'), TextInput::make('phone')->tel(), TextInput::make('email')->email(), DatePicker::make('start_date')->label('Alta'), Select::make('workCategories')->label('Tipos de trabajo')->relationship('workCategories', 'name')->multiple()->searchable()->preload()->helperText('Determina en qué servicios puede ser candidato este empleado.')->columnSpanFull(), Toggle::make('active')->default(true)])->columns(2)]);
    }

    public static function infolist(Schema $s): Schema
    {
        return $s->components([Section::make('Empleado')->schema([TextEntry::make('employee_code')->label('Código'), TextEntry::make('name'), TextEntry::make('position')->label('Puesto'), TextEntry::make('user.email')->label('Cuenta NOVA'), TextEntry::make('communityDepartments.name')->label('Departamentos')->badge(), TextEntry::make('workCategories.name')->label('Tipos de trabajo')->badge(), TextEntry::make('community_shifts_count')->label('Turnos'), TextEntry::make('community_attendances_count')->label('Registros')])->columns(2)]);
    }

    public static function table(Table $t): Table
    {
        return $t->columns([
            TextColumn::make('employee_code')->label('Código')->searchable(), 
            TextColumn::make('name')->label('Empleado')->searchable(), 
TextColumn::make('email')->searchable(),
            TextColumn::make('position')->label('Puesto'), 
            TextColumn::make('workCategories.name')->label('Tipos de trabajo')->badge(), 
            TextColumn::make('communityDepartments.name')->label('Departamentos')->badge(), 
            IconColumn::make('active')->boolean()])->filters([TernaryFilter::make('active')])
        ->recordActions([
            EditAction::make('Editar'),
            DeleteAction::make('Eliminar'),
        ])->headerActions([
            CreateAction::make(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['orders','tasks','incidents','communityDepartments', 'workCategories'])
            ->withCount(['orders','tasks','incidents','communityShifts', 'communityAttendances']);
    }

    public static function getRelations(): array
    {
        return [RelationManagers\DepartmentsRelationManager::class, RelationManagers\ShiftsRelationManager::class,
            RelationManagers\AttendancesRelationManager::class,
            RelationManagers\WorkOrdersRelationManager::class,
            RelationManagers\WorkTasksRelationManager::class,
            RelationManagers\IncidentsRelationManager::class,

        ];
    }

    public static function getPages(): array
    {
        return ['index' => Pages\ListEmployees::route('/'), 'create' => Pages\CreateEmployee::route('/create'), 'view' => Pages\ViewEmployee::route('/{record}'), 'edit' => Pages\EditEmployee::route('/{record}/edit'), 'departments' => Pages\ManageEmployeeDepartments::route('/{record}/departments'), 'shifts' => Pages\ManageEmployeeShifts::route('/{record}/shifts'), 'attendances' => Pages\ManageEmployeeAttendances::route('/{record}/attendances'), 
        'plans' => Pages\ManageEmployeePlans::route('/{record}/plans'), 
        'orders' => Pages\ManageEmployeeOrders::route('/{record}/orders'),
        'tasks' => Pages\ManageEmployeeTasks::route('/{record}/tasks'),
        'incidents' => Pages\ManageEmployeeIncidents::route('/{record}/incidents'),
        ];
    }
}
