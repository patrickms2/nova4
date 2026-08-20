<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages\CreateCommunityOwnerDocument;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages\EditCommunityOwnerDocument;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages\ListCommunityOwnerDocuments;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Pages\ViewCommunityOwnerDocument;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Schemas\CommunityOwnerDocumentForm;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Schemas\CommunityOwnerDocumentInfolist;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Tables\CommunityOwnerDocumentsTable;
use App\Models\CommunityOwnerDocument;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CommunityOwnerDocumentResource extends Resource
{
    protected static ?string $model = CommunityOwnerDocument::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static string|\UnitEnum|null $navigationGroup = 'Propietarios';

    protected static ?string $navigationLabel = 'Documentos';

    protected static ?string $modelLabel = 'Documento';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return CommunityOwnerDocumentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return CommunityOwnerDocumentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CommunityOwnerDocumentsTable::configure($table);
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
            'index' => ListCommunityOwnerDocuments::route('/'),
            'create' => CreateCommunityOwnerDocument::route('/create'),
            'view' => ViewCommunityOwnerDocument::route('/{record}'),
            'edit' => EditCommunityOwnerDocument::route('/{record}/edit'),
        ];
    }
}
