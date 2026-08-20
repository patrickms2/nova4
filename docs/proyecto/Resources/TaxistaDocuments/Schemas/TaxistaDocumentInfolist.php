<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Schemas;

use App\Models\TaxistaDocument;
use App\Support\TaxistaDocumentTypes;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaxistaDocumentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen del documento')
                    ->schema([
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->schema([
                                TextEntry::make('title')
                                    ->label('Titulo')
                                    ->weight(FontWeight::SemiBold)
                                    ->columnSpanFull(),
                                TextEntry::make('uploaded_at')
                                    ->label('Fecha subida')
                                    ->formatStateUsing(fn (mixed $state, TaxistaDocument $record): string => ($record->uploaded_at ?? $record->created_at)?->format('d/m/Y H:i') ?? '-'),
                                TextEntry::make('document_type')
                                    ->label('Tipo')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => TaxistaDocumentTypes::label($state)),
                                TextEntry::make('meta.reference')
                                    ->label('Referencia')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => filled($state) ? $state : 'Sin referencia'),
                                IconEntry::make('is_favorite')
                                    ->label('Favorito')
                                    ->boolean()
                                    ->trueIcon('heroicon-s-star')
                                    ->falseIcon('heroicon-o-star')
                                    ->trueColor('warning')
                                    ->falseColor('gray'),
                            ]),

                        TextEntry::make('file_name')
                            ->label('Nombre fichero y enlace')
                            ->getStateUsing(fn (TaxistaDocument $record): string => basename((string) $record->file_path))
                            ->placeholder('-')
                            ->weight(FontWeight::SemiBold),
                        TextEntry::make('file_preview')
                            ->label('Ver')
                            ->html()
                            ->getStateUsing(function (TaxistaDocument $record): string {
                                $url = self::resolveDocumentPublicUrl($record);

                                if (! $url) {
                                    return '-';
                                }

                                $safeUrl = e($url);

                                return "<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\">Abrir archivo</a>";
                            })
                            ->visible(fn (TaxistaDocument $record): bool => filled(self::resolveDocumentPublicUrl($record))),
                        TextEntry::make('file_download')
                            ->label('Descargar')
                            ->html()
                            ->getStateUsing(function (TaxistaDocument $record): string {
                                $url = self::resolveDocumentPublicUrl($record);

                                if (! $url) {
                                    return '-';
                                }

                                $safeUrl = e($url);

                                return "<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\" download>Descargar</a>";
                            })
                            ->visible(fn (TaxistaDocument $record): bool => filled(self::resolveDocumentPublicUrl($record))),
                        TextEntry::make('notes')
                            ->label('Notas')
                            ->getStateUsing(fn (TaxistaDocument $record): string => self::resolveNotes($record))
                            ->placeholder('-')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    // Document types are centralized in App\Support\TaxistaDocumentTypes.

    private static function resolveDocumentStoragePath(TaxistaDocument $record): ?string
    {
        $path = trim((string) $record->file_path);

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'documentos/')) {
            $path = Str::after($path, 'documentos/');
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        return $path;
    }

    private static function resolveDocumentPublicUrl(TaxistaDocument $record): ?string
    {
        $path = self::resolveDocumentStoragePath($record);

        if (! $path) {
            return null;
        }

        if (Storage::disk('documentos')->exists($path)) {
            $baseUrl = (string) config('filesystems.disks.documentos.url');

            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/').'/'.ltrim($path, '/');
            }
        }

        $publicPath = Str::startsWith($path, 'documentos/') ? $path : 'documentos/'.$path;

        $publicBaseUrl = (string) config('filesystems.disks.public.url');

        if (Storage::disk('public')->exists($publicPath) && $publicBaseUrl !== '') {
            return rtrim($publicBaseUrl, '/').'/'.ltrim($publicPath, '/');
        }

        if (Storage::disk('public')->exists($path) && $publicBaseUrl !== '') {
            return rtrim($publicBaseUrl, '/').'/'.ltrim($path, '/');
        }

        return null;
    }

    private static function resolveNotes(TaxistaDocument $record): string
    {
        $notes = data_get($record->meta, 'notes');

        if (is_array($notes)) {
            return json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        if (filled($notes)) {
            return (string) $notes;
        }

        if (filled($record->meta)) {
            return json_encode($record->meta, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        return '-';
    }
}
