<?php

namespace App\Filament\App\Community\Resources\People;

use App\Filament\App\Community\Resources\People\Pages\CreatePerson;
use App\Filament\App\Community\Resources\People\Pages\EditPerson;
use App\Filament\App\Community\Resources\People\Pages\ListPeople;
use App\Filament\App\Community\Resources\People\Pages\ViewPerson;
use App\Filament\App\Community\Resources\People\Schemas\PersonForm;
use App\Filament\App\Community\Resources\People\Schemas\PersonInfolist;
use App\Filament\App\Community\Resources\People\Tables\PeopleTable;
use App\Models\Person;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class PersonResource extends Resource
{
    protected static ?string $model = Person::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';
    protected static ?string $navigationParentGroup = 'Usuarios';
    protected static ?string $navigationLabel = 'Usuarios';

    protected static ?string $modelLabel = 'Usuario';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'display_name';

    protected static ?string $slug = 'community-users';

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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('communities')->with(['communities', 'user'])->withCount(['properties', 'communityDocuments', 'communityTickets']);
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
