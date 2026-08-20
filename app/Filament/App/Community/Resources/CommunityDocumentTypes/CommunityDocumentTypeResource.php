<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentTypes;

use App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages\CreateCommunityDocumentType;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages\EditCommunityDocumentType;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages\ListCommunityDocumentTypes;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Pages\ViewCommunityDocumentType;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Schemas\CommunityDocumentTypeForm;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Schemas\CommunityDocumentTypeInfolist;
use App\Filament\App\Community\Resources\CommunityDocumentTypes\Tables\CommunityDocumentTypesTable;
use App\Models\CommunityDocumentType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityDocumentTypeResource extends Resource
{
    protected static ?string $model = CommunityDocumentType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|\UnitEnum|null $navigationGroup = 'Empresa';

    protected static ?string $navigationLabel = 'Tipos de documentos';

    protected static ?string $modelLabel = 'Tipo de documento';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return CommunityDocumentTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityDocumentTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityDocumentTypesTable::configure($table);
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
            'index' => ListCommunityDocumentTypes::route('/'),
            'create' => CreateCommunityDocumentType::route('/create'),
            'view' => ViewCommunityDocumentType::route('/{record}'),
            'edit' => EditCommunityDocumentType::route('/{record}/edit'),
        ];
    }
}
