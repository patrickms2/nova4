<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports;

use App\Filament\App\Community\Resources\CommunityDocumentImports\Pages\CreateCommunityDocumentImport;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Pages\EditCommunityDocumentImport;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Pages\ListCommunityDocumentImports;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Pages\ViewCommunityDocumentImport;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Schemas\CommunityDocumentImportForm;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Schemas\CommunityDocumentImportInfolist;
use App\Filament\App\Community\Resources\CommunityDocumentImports\Tables\CommunityDocumentImportsTable;
use App\Models\CommunityDocumentImport;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityDocumentImportResource extends Resource
{
    protected static ?string $model = CommunityDocumentImport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArchiveBoxArrowDown;

    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';

    protected static ?string $navigationLabel = 'Documentos en lote';

    protected static ?string $modelLabel = 'Importación documental';

    protected static ?int $navigationSort = 7;

    protected static ?string $recordTitleAttribute = 'original_name';

    public static function form(Schema $schema): Schema
    {
        return CommunityDocumentImportForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityDocumentImportInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityDocumentImportsTable::configure($table);
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
            'index' => ListCommunityDocumentImports::route('/'),
            'create' => CreateCommunityDocumentImport::route('/create'),
            'view' => ViewCommunityDocumentImport::route('/{record}'),
            'edit' => EditCommunityDocumentImport::route('/{record}/edit'),
        ];
    }
}
