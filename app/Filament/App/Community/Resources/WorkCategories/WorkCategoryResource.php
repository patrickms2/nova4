<?php

namespace App\Filament\App\Community\Resources\WorkCategories;

use App\Filament\App\Community\Resources\WorkCategories\Pages\CreateWorkCategory;
use App\Filament\App\Community\Resources\WorkCategories\Pages\EditWorkCategory;
use App\Filament\App\Community\Resources\WorkCategories\Pages\ListWorkCategories;
use App\Filament\App\Community\Resources\WorkCategories\Pages\ViewWorkCategory;
use App\Models\WorkCategory;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkCategoryResource extends Resource
{
    protected static ?string $model = WorkCategory::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static ?string $navigationLabel = 'Categorías de tareas';

    protected static ?string $modelLabel = 'Categoría de tarea';

    protected static ?string $pluralModelLabel = 'Categorías de tareas';

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Tipo de servicio')->description('Agrupa servicios y define qué empleados están capacitados para realizarlos.')->schema([
                TextInput::make('name')->label('Nombre')->required()->unique(ignoreRecord: true)->maxLength(255),
                TextInput::make('sort')->label('Orden')->numeric()->default(0)->required(),
                Toggle::make('active')->label('Activo')->default(true),
            ])->columns(2),
        ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Tipo de servicio')->schema([
            TextEntry::make('name')->label('Nombre'),
            TextEntry::make('catalog_items_count')->label('Servicios'),
            TextEntry::make('employees_count')->label('Empleados capacitados'),
            IconEntry::make('active')->label('Activo')->boolean(),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('sort')->columns([
            TextColumn::make('name')->label('Tipo de servicio')->searchable()->sortable(),
            TextColumn::make('catalog_items_count')->label('Servicios')->counts('catalogItems')->badge(),
            TextColumn::make('employees_count')->label('Empleados')->counts('employees')->badge(),
            TextColumn::make('sort')->label('Orden')->sortable(),
            IconColumn::make('active')->label('Activo')->boolean(),
        ])->filters([TernaryFilter::make('active')->label('Activo')]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withCount(['catalogItems', 'employees']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWorkCategories::route('/'),
            'create' => CreateWorkCategory::route('/create'),
            'view' => ViewWorkCategory::route('/{record}'),
            'edit' => EditWorkCategory::route('/{record}/edit'),
        ];
    }
}
