<?php

namespace App\Filament\App\Community\Resources\Communities\Pages;

use App\Filament\App\Community\Resources\Communities\CommunityResource;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use App\Filament\App\Community\Resources\WorkCatalogs\WorkCatalogResource;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Builder;
use App\Models\WorkCatalog;
use Filament\Schemas\Concerns\InteractsWithSchemas;

class ManagePlanCatalogs extends Page implements HasTable
{
    use InteractsWithTable;
    use InteractsWithRecord;
    use InteractsWithSchemas;

    protected static string $resource = CommunityResource::class;
    protected string $view = 'filament.app.community.communities.catalogs';

    protected static ?string $title = 'Tipos servicios';
    protected static string|\UnitEnum|null $navigationGroup = 'Mantenimiento';

    protected static ?string $navigationLabel = 'Tipos servicios';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([Select::make('work_catalog_id')->label('Catálogo')->relationship('catalog', 'title')->searchable()->preload(), TextInput::make('title')->label('Tarea')->required(), Textarea::make('instructions')->label('Instrucciones'), Textarea::make('requirements')->label('Requisitos'), TextInput::make('sort')->label('Orden')->numeric()->default(0), Toggle::make('active')->label('Activa')->default(true), Repeater::make('days')->label('Días')->relationship()->schema([Select::make('day_of_week')->label('Día')->options([1 => 'Lunes', 2 => 'Martes', 3 => 'Miércoles', 4 => 'Jueves', 5 => 'Viernes', 6 => 'Sábado', 7 => 'Domingo'])->required()])->columnSpanFull()]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema->components([Section::make('Servicio')->schema([
            TextEntry::make('category.name')->label('Tipo de servicio'), TextEntry::make('title')->label('Servicio'), TextEntry::make('code')->label('Código')->placeholder('—'), TextEntry::make('default_priority')->label('Prioridad')->badge(), TextEntry::make('instructions')->label('Instrucciones')->columnSpanFull(), TextEntry::make('requirements')->label('Requisitos')->columnSpanFull(), TextEntry::make('plan_items_count')->label('Usos en planes'), IconEntry::make('active')->label('Activo')->boolean(),
        ])->columns(2)]);
    }

    public static function getEloquentQuery(): Builder
    {
        return WorkCatalog::query()->with('category')->withCount('planItems');
    }
    
    public static function table(Table $table): Table
    {
        return $table
        ->query(fn (): Builder => self::getEloquentQuery())
        ->defaultSort('title')
        ->columns([
            TextColumn::make('title')->label('Servicio')->searchable()->sortable(), TextColumn::make('category.name')->label('Tipo')->badge()->sortable(), TextColumn::make('code')->label('Código')->searchable()->toggleable(), TextColumn::make('default_priority')->label('Prioridad')->badge(), TextColumn::make('plan_items_count')->label('Planes')->counts('planItems')->badge(), IconColumn::make('active')->label('Activo')->boolean(),
        ])->filters([SelectFilter::make('work_category_id')->label('Tipo')->relationship('category', 'name')->searchable()->preload(), TernaryFilter::make('active')->label('Activo')])
        ->recordActions([
            DeleteAction::make(),
            EditAction::make('Editar')->schema([
                Section::make('Servicio')->description('Trabajo reutilizable que puede incorporarse a los planes de las comunidades.')->schema([
            Select::make('work_category_id')->label('Tipo de servicio')->relationship('category', 'name')->searchable()->preload()->required(),
            TextInput::make('title')->label('Servicio')->required()->maxLength(255),
            TextInput::make('code')->label('Código')->unique(ignoreRecord: true)->maxLength(255),
            Select::make('default_priority')->label('Prioridad habitual')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal')->required(),
            Textarea::make('instructions')->label('Instrucciones')->columnSpanFull(),
            Textarea::make('requirements')->label('Requisitos')->columnSpanFull(),
            Toggle::make('active')->label('Activo')->default(true),
        ])->columns(2)
            ]),
        ])
        ->headerActions([
                CreateAction::make('Crear')
                ->schema([ 
                    Section::make('Servicio')->description('Trabajo reutilizable que puede incorporarse a los planes de las comunidades.')->schema([
                        Select::make('work_category_id')->label('Tipo de servicio')->relationship('category', 'name')->searchable()->preload()->required(),
                        TextInput::make('title')->label('Servicio')->required()->maxLength(255),
                        TextInput::make('code')->label('Código')->unique(ignoreRecord: true)->maxLength(255),
                        Select::make('default_priority')->label('Prioridad habitual')->options(['low' => 'Baja', 'normal' => 'Normal', 'high' => 'Alta', 'urgent' => 'Urgente'])->default('normal')->required(),
                        Textarea::make('instructions')->label('Instrucciones')->columnSpanFull(),
                        Textarea::make('requirements')->label('Requisitos')->columnSpanFull(),
                        Toggle::make('active')->label('Activo')->default(true),
                    ])
                    ->columns(2)
                ])->slideOver()]);
    }
}
