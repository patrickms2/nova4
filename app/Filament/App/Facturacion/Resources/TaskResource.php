<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Facturacion;
use App\Filament\App\Facturacion\Resources\TaskResource\Pages;
use App\Filament\App\Facturacion\Resources\TaskResource\Schemas\TaskForm;
use App\Filament\App\Facturacion\Resources\TaskResource\Tables\TasksTable;
use App\Models\Task;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCheckCircle;

    protected static ?string $navigationLabel = 'Tareas';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 40;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return TaskForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return TasksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            'create' => Pages\CreateTask::route('/create'),
            'edit' => Pages\EditTask::route('/{record}/edit'),
            'calendar' => Pages\CalendarTasks::route('/calendar'),
        ];
    }
}
