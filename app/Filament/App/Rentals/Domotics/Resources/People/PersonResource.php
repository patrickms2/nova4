<?php

namespace App\Filament\App\Rentals\Domotics\Resources\People;

use App\Filament\App\Rentals\Domotics\Resources\People\Pages\CreatePerson;
use App\Filament\App\Rentals\Domotics\Resources\People\Pages\EditPerson;
use App\Filament\App\Rentals\Domotics\Resources\People\Pages\ListPeople;
use App\Filament\App\Rentals\Domotics\Resources\People\Pages\ViewPerson;
use App\Filament\App\Rentals\Domotics\Resources\People\Schemas\PersonForm;
use App\Filament\App\Rentals\Domotics\Resources\People\Schemas\PersonInfolist;
use App\Filament\App\Rentals\Domotics\Resources\People\Tables\PeopleTable;
use App\Filament\App\Rentals\Rentals;
use App\Models\Person;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;
    protected static ?string $cluster = Rentals::class;

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return 'Nova Property';
    }

    public static function getNavigationSort(): ?int
    {
        return 2;
    }

    public static function form(Schema $schema): Schema
    {
        return PersonForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeopleTable::configure($table);
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
            'index' => ListPeople::route('/'),
            'create' => CreatePerson::route('/create'),
            'view' => ViewPerson::route('/{record}'),
            'edit' => EditPerson::route('/{record}/edit'),
        ];
    }
}
