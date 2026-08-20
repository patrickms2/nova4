<?php

namespace App\Filament\Resources\NovaIntentToServerMapping;

use App\Filament\Resources\NovaIntentToServerMapping\Pages\CreateNovaIntentToServerMapping;
use App\Filament\Resources\NovaIntentToServerMapping\Pages\EditNovaIntentToServerMapping;
use App\Filament\Resources\NovaIntentToServerMapping\Pages\ListNovaIntentToServerMappings;
use App\Filament\Resources\NovaIntentToServerMapping\Pages\ViewNovaIntentToServerMapping;
use App\Filament\Resources\NovaIntentToServerMapping\Schemas\NovaIntentToServerMappingForm;
use App\Filament\Resources\NovaIntentToServerMapping\Schemas\NovaIntentToServerMappingInfolist;
use App\Filament\Resources\NovaIntentToServerMapping\Tables\NovaIntentToServerMappingsTable;
use App\Models\NovaIntentToServerMapping;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NovaIntentToServerMappingResource extends Resource
{
    protected static ?string $model = NovaIntentToServerMapping::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Intent to Server Mapping';

    protected static ?string $modelLabel = 'Intent Mapping';
    protected static string|\UnitEnum|null $navigationGroup = 'MCP';
    protected static ?string $navigationParentGroup = 'IA';

    protected static ?string $pluralModelLabel = 'Intent Mappings';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return NovaIntentToServerMappingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return NovaIntentToServerMappingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NovaIntentToServerMappingsTable::configure($table);
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
            'index' => ListNovaIntentToServerMappings::route('/'),
            'create' => CreateNovaIntentToServerMapping::route('/create'),
            'view' => ViewNovaIntentToServerMapping::route('/{record}'),
            'edit' => EditNovaIntentToServerMapping::route('/{record}/edit'),
        ];
    }
}
