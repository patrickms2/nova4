<?php

namespace App\Filament\App\Resources\TaxiDocumentTypes;

use App\Filament\App\Resources\TaxiDocumentTypes\Pages\ListTaxiDocumentTypes;

use App\Models\TaxiCentral\DocumentType;
use Archilex\AdvancedTables\AdvancedTables;
use Awcodes\BadgeableColumn\Components\Badge;
use Awcodes\BadgeableColumn\Components\BadgeableColumn;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ColorColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Guava\IconPicker\Forms\Components\IconPicker;
use UnitEnum;

class TaxiDocumentTypeResource extends Resource
{
    use AdvancedTables;

    protected static ?string $model = DocumentType::class;

    protected static bool $isGloballySearchable = true;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-group';


    protected static ?string $navigationLabel = 'Tipos de documento';

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?int $navigationSort = 10;

    protected static bool $isScopedToTenant = false;
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),
            TextInput::make('code')
                ->label('Codigo')
                ->maxLength(50)
                ->unique(ignoreRecord: true),
            ColorPicker::make('color')
                ->label('Color')
                ->default('#ef4444'),
            IconPicker::make('icon')
                ->label('Icono')
                ->sets(['heroicons']),
            TextInput::make('order')
                ->label('Orden')
                ->numeric()
                ->default(0)
                ->minValue(0),
            Toggle::make('favorito')
                ->label('Destacado')
                ->default(true),
            Toggle::make('is_active')
                ->label('Activo')
                ->default(true),
            Textarea::make('description')
                ->label('Descripcion')
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('Nombre')->searchable()->sortable(),
                BadgeableColumn::make('name')
                    ->separator('')
                    ->prefixBadges([
                        Badge::make('code')
                            ->color('gray')
                            ->label(fn(DocumentType $record) => $record->code),
                    ])
                    ->searchable()
                    ->sortable(),
                TextColumn::make('description')->label('Desc.')->sortable(),

                ColorColumn::make('color')
                    ->label('Color')
                    ->toggleable(isToggledHiddenByDefault: false),
                IconColumn::make('icon')
                    ->label('Icono')
                    ->icon(fn(?string $state): string => $state ?: 'heroicon-o-document-text')
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('order')->label('Orden')->sortable(),
                ToggleColumn::make('favorito')->label('Destacado')->sortable(),
                ToggleColumn::make('is_active')->label('Activo')->sortable(),
                TextColumn::make('created_at')->label('Creado')->dateTime('d/m/Y H:i')->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make()->slideOver(false),
                DeleteAction::make(),
            ])
            ->filters([
                TernaryFilter::make('is_active')->label('Activo'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('order');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTaxiDocumentTypes::route('/'),
        ];
    }

}
