<?php

namespace App\Filament\App\Rentals\Resources;

use App\Filament\App\Rentals\Resources\RentalDocumentResource\Pages;
use App\Filament\App\Rentals\Resources\RentalDocumentResource\Widgets;

use App\Models\RentalDocument;
use App\Models\RentalInventoryItem;
use App\Models\RentalProperty;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\App\Rentals\Rentals;

class RentalDocumentResource extends Resource
{
    protected static ?string $model = RentalDocument::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $pluralModelLabel = 'Documentos';
    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;
    protected static ?string $modelLabel = 'Documento';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Relación')
                    ->schema([
                        Select::make('documentable_type')
                            ->label('Entidad relacionada')
                            ->options([
                                RentalProperty::class => 'Propiedad',
                                RentalInventoryItem::class => 'Inventario',
                            ])
                            ->required(),
                        TextInput::make('documentable_id')
                            ->label('ID de la entidad')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Documento')
                    ->schema([
                        Select::make('category')
                            ->label('Categoría')
                            ->options(RentalDocument::categories())
                            ->required(),
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        FileUpload::make('file_path')
                            ->label('Archivo')
                            ->directory('rental-documents')
                            ->nullable(),
                        DatePicker::make('expiry_date')
                            ->label('Fecha de caducidad'),
                        KeyValue::make('meta')
                            ->label('Metadatos extra'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Categoría')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => RentalDocument::categories()[$state] ?? $state),
                TextColumn::make('documentable_type')
                    ->label('Entidad')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        RentalProperty::class => 'Propiedad',
                        RentalInventoryItem::class => 'Inventario',
                        default => $state,
                    }),
                TextColumn::make('expiry_date')
                    ->label('Caducidad')
                    ->date('d M Y'),
            ])
            ->filters([
                SelectFilter::make('category')
                    ->options(RentalDocument::categories()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Crear documento')
                    ->icon(Heroicon::OutlinedPlus)
                    ->slideOver(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['documentable']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalDocuments::route('/'),
            'kanban' => Pages\KanbanRentalDocuments::route('/kanban'),
            'view' => Pages\ViewRentalDocument::route('/{record}'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\RentalDocumentStats::class,
        ];
    }
}
