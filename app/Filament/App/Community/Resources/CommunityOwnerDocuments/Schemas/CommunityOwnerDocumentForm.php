<?php

namespace App\Filament\App\Community\Resources\CommunityOwnerDocuments\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityOwnerDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Documento del propietario')->schema([
                Select::make('community_id')->label('Comunidad')->relationship('community', 'name')->searchable()->preload(), Select::make('person_id')->label('Propietario')->relationship('person', 'display_name')->searchable()->preload()->required(),
                Select::make('property_id')->label('Propiedad')->relationship('property', 'name')->searchable()->preload(), Select::make('community_document_type_id')->label('Tipo')->relationship('documentType', 'name')->searchable()->preload(),
                TextInput::make('title')->label('Título')->required()->columnSpanFull(), FileUpload::make('path')->label('Archivo')->disk('local')->directory('community/owners')->visibility('private')->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])->required()->columnSpanFull(),
                Select::make('status')->label('Estado')->options(['active' => 'Activo', 'expired' => 'Caducado', 'archived' => 'Archivado'])->default('active')->required(), DatePicker::make('document_date')->label('Fecha'), DatePicker::make('expires_at')->label('Caduca'),
            ])->columns(2)]);
    }
}
