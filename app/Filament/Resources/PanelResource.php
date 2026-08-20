<?php

namespace App\Filament\Resources;

use Filament\Support\Icons\Heroicon;

use App\Filament\Resources\Panel\Pages;
use App\Models\Panel;
use Filament\Forms;
use Filament\Actions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema as Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use BackedEnum;
use UnitEnum;
class PanelResource extends Resource
{
    protected static ?string $model = Panel::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|\UnitEnum|null $navigationGroup = 'Paneles';
    protected static ?string $navigationParentGroup = 'Nova Hub';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Panel Information')
                    ->schema([
                        Section::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->maxLength(255)
                                    ->unique(),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->maxLength(65535),

                        Section::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('icon')
                                    ->default(Heroicon::OutlinedCube)
                                    ->helperText('Heroicon name'),

                                Forms\Components\TextInput::make('navigation_group')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('navigation_sort')
                                    ->numeric()
                                    ->default(0),
                            ]),

                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ]),

                Section::make('Model Schema')
                    ->schema([
                        Forms\Components\KeyValue::make('model_schema')
                            ->keyLabel('Property')
                            ->valueLabel('Value')
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('navigation_group')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('fields_count')
                    ->label('Fields')
                    ->getStateUsing(fn ($record) => $record->fields()->count())
                    ->sortable(),

                Tables\Columns\TextColumn::make('relations_count')
                    ->label('Relations')
                    ->getStateUsing(fn ($record) => $record->relations()->count())
                    ->sortable(),

                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('navigation_group')
                    ->options(fn () => Panel::distinct()->pluck('navigation_group', 'navigation_group')->filter()->all()),

                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Actions\ViewAction::make(),
                Actions\EditAction::make(),
                Actions\DeleteAction::make(),
                Actions\Action::make('generate_code')
                    ->label('Generate Code')
                    ->icon(Heroicon::OutlinedCodeBracket)
                    ->action(function ($record) {
                        $panelBuilder = new \App\Livewire\PanelBuilder();
                        $panelBuilder->generateCode($record->id);
                        \Filament\Notifications\Notification::make()
                            ->title('Code Generated')
                            ->success()
                            ->send();
                    }),
            ])
            ->toolbarActions([
                Actions\DeleteBulkAction::make()->deselectRecordsAfterCompletion(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPanels::route('/'),
            'create' => Pages\CreatePanel::route('/create'),
            'edit' => Pages\EditPanel::route('/{record}/edit'),
        ];
    }
}
