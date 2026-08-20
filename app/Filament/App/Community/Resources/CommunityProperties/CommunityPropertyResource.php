<?php

namespace App\Filament\App\Community\Resources\CommunityProperties;

use App\Filament\App\Community\Resources\CommunityProperties\Pages\CreateCommunityProperty;
use App\Filament\App\Community\Resources\CommunityProperties\Pages\EditCommunityProperty;
use App\Filament\App\Community\Resources\CommunityProperties\Pages\ListCommunityProperties;
use App\Filament\App\Community\Resources\CommunityProperties\Pages\ViewCommunityProperty;
use App\Filament\App\Community\Resources\CommunityProperties\Schemas\CommunityPropertyForm;
use App\Filament\App\Community\Resources\CommunityProperties\Schemas\CommunityPropertyInfolist;
use App\Filament\App\Community\Resources\CommunityProperties\Tables\CommunityPropertiesTable;
use App\Models\CommunityProperty;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Pages\Enums\SubNavigationPosition;

class CommunityPropertyResource extends Resource
{
    protected static ?string $model = CommunityProperty::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedHomeModern;

    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';
    protected static ?string $navigationParentGroup = 'Propietarios';
    protected static ?string $navigationLabel = 'Propiedades';

        protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static ?string $modelLabel = 'Propiedad';
    protected static ?string $pluralModelLabel = 'Propiedades';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CommunityPropertyForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityPropertyInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityPropertiesTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['community', 'people','owners']);
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
            'index' => ListCommunityProperties::route('/'),
            'create' => CreateCommunityProperty::route('/create'),
            'view' => ViewCommunityProperty::route('/{record}'),
            'edit' => EditCommunityProperty::route('/{record}/edit'),
        ];
    }
}
