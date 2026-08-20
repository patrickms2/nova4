<?php

namespace App\Filament\App\Community\Resources\WorkCatalogs;

use App\Filament\App\Community\Resources\WorkCatalogs\Pages\CreateWorkCatalog;
use App\Filament\App\Community\Resources\WorkCatalogs\Pages\EditWorkCatalog;
use App\Filament\App\Community\Resources\WorkCatalogs\Pages\ListWorkCatalogs;
use App\Filament\App\Community\Resources\WorkCatalogs\Pages\ViewWorkCatalog;
use App\Models\WorkCatalog;
use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
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
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class WorkCatalogResource extends Resource
{
    protected static ?string $model = WorkCatalog::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

    protected static ?string $navigationLabel = 'Tipos de Servicios';

    protected static ?string $modelLabel = 'Servicio';

    protected static ?string $pluralModelLabel = 'Servicios';

    protected static string|UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([Section::make('Servicio')->description('Trabajo reutilizable que puede incorporarse a los planes de las comunidades.')->schema([
            Select::make('work_category_id')->label('Tipo de servicio')->relationship('category', 'name')->searchable()->preload()->required(),
            TextInput::make('title')->label('Servicio')->required()->maxLength(255),
            TextInput::make('code')->label('Código')->unique(ignoreRecord: true)->maxLength(255),
            Select::make('default_priority')->label('Prioridad habitual')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal')->required(),
            Textarea::make('instructions')->label('Instrucciones')->columnSpanFull(),
            Textarea::make('requirements')->label('Requisitos')->columnSpanFull(),
            Toggle::make('active')->label('Activo')->default(true),
        ])->columns(2)]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Servicio')->schema([
            TextEntry::make('category.name')->label('Tipo de servicio'), TextEntry::make('title')->label('Servicio'), TextEntry::make('code')->label('Código')->placeholder('—'), TextEntry::make('default_priority')->label('Prioridad')->badge(), TextEntry::make('instructions')->label('Instrucciones')->columnSpanFull(), TextEntry::make('requirements')->label('Requisitos')->columnSpanFull(), TextEntry::make('plan_items_count')->label('Usos en planes'), IconEntry::make('active')->label('Activo')->boolean(),
        ])->columns(2)]);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('title')->columns([
            TextColumn::make('title')->label('Servicio')->searchable()->sortable(), TextColumn::make('category.name')->label('Tipo')->badge()->sortable(), TextColumn::make('code')->label('Código')->searchable()->toggleable(), TextColumn::make('default_priority')->label('Prioridad')->badge(), TextColumn::make('plan_items_count')->label('Planes')->counts('planItems')->badge(), IconColumn::make('active')->label('Activo')->boolean(),
        ])->filters([SelectFilter::make('work_category_id')->label('Tipo')->relationship('category', 'name')->searchable()->preload(), TernaryFilter::make('active')->label('Activo')]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category')->withCount('planItems');
    }

    public static function getPages(): array
    {
        return ['index' => ListWorkCatalogs::route('/'), 'create' => CreateWorkCatalog::route('/create'), 'view' => ViewWorkCatalog::route('/{record}'), 'edit' => EditWorkCatalog::route('/{record}/edit')];
    }
}
