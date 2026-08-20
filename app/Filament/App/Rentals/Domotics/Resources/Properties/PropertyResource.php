<?php

namespace App\Filament\App\Rentals\Domotics\Resources\Properties;

use App\Filament\App\Rentals\Domotics\Resources\Properties\Pages\CreateProperty;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Pages\EditProperty;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Pages\ListProperties;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Pages\ViewProperty;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Schemas\PropertyForm;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Schemas\PropertyInfolist;
use App\Filament\App\Rentals\Domotics\Resources\Properties\Tables\PropertiesTable;
use App\Filament\App\Rentals\Rentals;
use App\Models\Property;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use UnitEnum;

class PropertyResource extends Resource
{
    protected static ?string $model = Property::class;

    // TODO: Remove this when the resource is ready
    protected static ?string $navigationLabel = 'Propiedades';

    protected static ?string $pluralModelLabel = 'Propiedades';

    protected static ?string $modelLabel = 'Propiedad';

    protected static string|\UnitEnum|null $navigationGroup = 'Nova Property';
    public static function getNavigationIcon(): string|BackedEnum|Htmlable|null
    {
        return Heroicon::Home;
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Property';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function form(Schema $schema): Schema
    {
        return PropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PropertiesTable::configure($table);
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
            'index' => ListProperties::route('/'),
            'create' => CreateProperty::route('/create'),
            'view' => ViewProperty::route('/{record}'),
            'edit' => EditProperty::route('/{record}/edit'),
        ];
    }
}
