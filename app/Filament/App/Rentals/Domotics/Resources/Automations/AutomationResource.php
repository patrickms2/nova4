<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Automations;

use App\Filament\App\Rentals\Domotics\Resources\Automations\Pages\CreateAutomation;
use App\Filament\App\Rentals\Domotics\Resources\Automations\Pages\EditAutomation;
use App\Filament\App\Rentals\Domotics\Resources\Automations\Pages\ListAutomations;
use App\Filament\App\Rentals\Domotics\Resources\Automations\Pages\ViewAutomation;
use App\Filament\App\Rentals\Rentals;
use App\Models\Automation;
use BackedEnum;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class AutomationResource extends Resource
{
    protected static ?string $model = Automation::class;
    protected static ?string $cluster = Rentals::class;

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::Bolt;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de la automatización')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(255),
                        Toggle::make('is_active')
                            ->label('Activa')
                            ->default(true),
                    ])
                    ->columns(2),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')->label('Nombre'),
                TextEntry::make('is_active')->label('Activa'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label('Activa')
                    ->boolean(),
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
            'index' => ListAutomations::route('/'),
            'create' => CreateAutomation::route('/create'),
            'view' => ViewAutomation::route('/{record}'),
            'edit' => EditAutomation::route('/{record}/edit'),
        ];
    }
}
