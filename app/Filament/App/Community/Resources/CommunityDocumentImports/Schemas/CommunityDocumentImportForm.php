<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityDocumentImportForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Carga masiva')->description('ZIP con PDFs o PDF. El nombre debe contener el documento del propietario o la referencia de la propiedad. Los archivos ambiguos quedan sin asignar.')->schema([Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload()->required(), Select::make('community_document_type_id')->label('Tipo documental')->relationship('documentType', 'name')->searchable()->preload(), FileUpload::make('source_path')->label('ZIP o PDF')->disk('local')->directory('community/imports')->visibility('private')->acceptedFileTypes(['application/zip', 'application/x-zip-compressed', 'application/pdf'])->required()->columnSpanFull()])->columns(2)]);
    }
}
