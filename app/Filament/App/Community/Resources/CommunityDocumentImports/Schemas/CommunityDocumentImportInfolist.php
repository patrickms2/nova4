<?php

namespace App\Filament\App\Community\Resources\CommunityDocumentImports\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CommunityDocumentImportInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([Section::make('Resultado de importación')->schema([TextEntry::make('original_name')->label('Archivo'), TextEntry::make('community.name')->label('Comunidad'), TextEntry::make('documentType.name')->label('Tipo'), TextEntry::make('status')->label('Estado')->badge(), TextEntry::make('files_found')->label('PDF encontrados'), TextEntry::make('documents_created')->label('Documentos asignados'), TextEntry::make('unmatched_files')->label('Sin asignar'), TextEntry::make('processed_at')->label('Procesado')->dateTime(), TextEntry::make('issues')->label('Incidencias')->formatStateUsing(fn (?array $state): string => collect($state ?? [])->map(fn (array $issue): string => ($issue['file'] ?? 'Importación').': '.($issue['reason'] ?? 'Error'))->implode("\n"))->columnSpanFull()])->columns(3)]);
    }
}
