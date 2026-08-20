<?php

namespace App\Filament\App\Community\Resources\Owners\Pages;

use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Schemas\CommunityOwnerDocumentForm;
use App\Filament\App\Community\Resources\CommunityOwnerDocuments\Tables\CommunityOwnerDocumentsTable;
use App\Filament\App\Community\Resources\Owners\OwnerResource;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
class ManageOwnerDocuments extends ManageRelatedRecords
{
    protected static string $resource = OwnerResource::class;

    protected static string $relationship = 'communityDocuments';

    protected static ?string $navigationLabel = 'Documentos';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->mutateDataUsing(fn (array $data): array => [...$data, 'uploaded_by' => auth()->id(), 'type' => 'other'])];
    }

    public function form(Schema $schema): Schema
    {
        return CommunityOwnerDocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return CommunityOwnerDocumentsTable::configure($table);
    }
}
