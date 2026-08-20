<?php

namespace App\Filament\App\Rentals\Resources;

use App\Enums\TaskStatus;
use App\Filament\App\Rentals\Resources\RentalTaskResource\Pages;
use App\Filament\App\Rentals\Resources\RentalTaskResource\Widgets;
use App\Models\Task;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Enums\SubNavigationPosition;
use App\Filament\App\Rentals\Rentals;

class RentalTaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?string $navigationLabel = 'Tareas';

    protected static ?string $pluralModelLabel = 'Tareas';

    protected static ?string $modelLabel = 'Tarea';

    protected static string|\UnitEnum|null $navigationGroup = 'Property OS';
    protected static ?string $cluster = Rentals::class;

    protected static ?int $navigationSort = 7;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tarea')
                    ->schema([
                        TextInput::make('title')
                            ->label('Título')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('description')
                            ->label('Descripción')
                            ->rows(3)
                            ->columnSpanFull(),
                        Select::make('status')
                            ->label('Estado')
                            ->options(TaskStatus::class)
                            ->default(TaskStatus::Todo->value)
                            ->required(),
                        Select::make('priority')
                            ->label('Prioridad')
                            ->options([
                                'low' => 'Baja',
                                'medium' => 'Media',
                                'high' => 'Alta',
                            ])
                            ->default('medium'),
                        Select::make('assigned_to')
                            ->label('Asignado a')
                            ->options(fn () => User::query()->pluck('name', 'id'))
                            ->searchable()
                            ->nullable(),
                        DatePicker::make('due_date')
                            ->label('Fecha de vencimiento'),
                        Select::make('type')
                            ->label('Tipo')
                            ->options([
                                'rental' => 'Vacacional',
                            ])
                            ->default('rental')
                            ->required()
                            ->hidden(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('due_date', 'asc')
            ->columns([
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => TaskStatus::tryFrom($state)?->getLabel() ?? $state),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                        default => $state,
                    }),
                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d M Y'),
                TextColumn::make('assignedTo.name')
                    ->label('Asignado')
                    ->default('Sin asignar'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(TaskStatus::class),
                SelectFilter::make('priority')
                    ->options([
                        'low' => 'Baja',
                        'medium' => 'Media',
                        'high' => 'Alta',
                    ]),
            ])
            ->recordActions([
                EditAction::make()->slideOver(),
                DeleteAction::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva tarea')
                    ->icon(Heroicon::OutlinedPlus)
                    ->slideOver(),
            ])
            ->toolbarActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['assignedTo']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRentalTasks::route('/'),
            'kanban' => Pages\KanbanRentalTasks::route('/kanban'),
            'calendar' => Pages\CalendarRentalTasks::route('/calendar'),
        ];
    }

    public static function getWidgets(): array
    {
        return [
            Widgets\RentalTaskStats::class,
        ];
    }
}
