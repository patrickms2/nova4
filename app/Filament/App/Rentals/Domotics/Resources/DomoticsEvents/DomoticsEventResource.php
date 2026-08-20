<?php

namespace App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents;

use App\Enums\DomoticsEventType;
use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages\ListDomoticsEvents;
use App\Filament\App\Rentals\Domotics\Resources\DomoticsEvents\Pages\ViewDomoticsEvent;
use App\Filament\App\Rentals\Rentals;
use App\Models\DomoticsEvent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use UnitEnum;

class DomoticsEventResource extends Resource
{
    protected static ?string $model = DomoticsEvent::class;

    protected static ?string $cluster = Rentals::class;

    protected static ?string $navigationLabel = 'Access Events';

    protected static ?string $modelLabel = 'access event';

    protected static ?string $pluralModelLabel = 'access events';

    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::ClipboardDocumentList;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Access';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('event_type')
                    ->label('Evento')->searchable()
                    ->badge(),
                TextColumn::make('accessPoint.name')
                    ->label('Punto de acceso'),
                TextColumn::make('accessGrant.person.display_name')
                    ->label('Persona')
                    ->placeholder('Sin persona'),
                TextColumn::make('user.email')
                    ->label('Usuario'),
            ])
            ->filters([
                SelectFilter::make('event_type')
                    ->options(DomoticsEventType::class)
                    ->multiple(),
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
            'index' => ListDomoticsEvents::route('/'),
            'view' => ViewDomoticsEvent::route('/{record}'),
        ];
    }
}
