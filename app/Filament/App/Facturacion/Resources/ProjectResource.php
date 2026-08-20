<?php

namespace App\Filament\App\Facturacion\Resources;

use App\Filament\App\Facturacion\Facturacion;
use App\Filament\App\Facturacion\Resources\ProjectResource\Pages;
use App\Filament\App\Facturacion\Resources\ProjectResource\Schemas\ProjectForm;
use App\Filament\App\Facturacion\Resources\ProjectResource\Tables\ProjectsTable;
use App\Models\Project;
use BackedEnum;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Resources\Resource;
use Filament\Schemas\Schema as Form;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Proyectos';

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;
    protected static ?string $cluster = Facturacion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Facturación';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 35;

    protected static bool $isScopedToTenant = false;

    public static function form(Form $form): Form
    {
        return ProjectForm::configure($form);
    }

    public static function table(Table $table): Table
    {
        return ProjectsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            'create' => Pages\CreateProject::route('/create'),
            'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
