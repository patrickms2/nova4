<?php

namespace App\Filament\App\Resources\TaxistaTickets\Schemas;

use App\Models\TaxistaTicket;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaxistaTicketInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Resumen del ticket')

                            ->columnSpanFull()
                    ->schema([
                        Grid::make([
                            'default' => 2,
                            'md' => 2,
                        ])
                                            ->columnSpanFull()

                            ->schema([
                                TextEntry::make('title')
                                    ->label('Título')
                                    ->weight(FontWeight::SemiBold)
                                    ->columnSpanFull(),
                                
                                TextEntry::make('user.name')
                                    ->label('Taxista')
                                    ->placeholder('Sin asignar'),
                                
                                TextEntry::make('department.name')
                                    ->label('Departamento')
                                    ->placeholder('Sin departamento'),
                                
                                    TextEntry::make('ticket_type')
                                    ->label('Tipo de Ticket')
                                    ->placeholder('Sin definir'),
                                
                                TextEntry::make('priority')
                                    ->label('Prioridad')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'baja' => 'Baja',
                                        'media' => 'Media',
                                        'alta' => 'Alta',
                                        default => 'Sin definir',
                                    }),
                                
                                TextEntry::make('status')
                                    ->label('Estado')
                                    ->badge()
                                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                                        'abierto' => 'Abierto',
                                        'en_progreso' => 'En progreso',
                                        'cerrado' => 'Cerrado',
                                        'resuelto' => 'Resuelto',
                                        'cancelado' => 'Cancelado',

                                        default => 'Sin definir',
                                    }),
                                
                                TextEntry::make('createdBy.name')
                                    ->label('Creado por')
                                    ->placeholder('Sin información'),
                                
                                TextEntry::make('assignedTo.name')
                                    ->label('Asignado a')
                                    ->placeholder('Sin asignar'),
                                
                                TextEntry::make('opened_at')
                                    ->label('Fecha apertura')
                                    ->formatStateUsing(fn (mixed $state, TaxistaTicket $record): string => ($record->opened_at ?? $record->created_at)?->format('d/m/Y H:i') ?? '-'),
                                
                                TextEntry::make('due_at')
                                    ->label('Fecha límite')
                                    ->formatStateUsing(fn (?string $state): string => $state ? \Carbon\Carbon::parse($state)->format('d/m/Y H:i') : 'Sin fecha límite'),
                                
                                IconEntry::make('is_screen_shot')
                                    ->label('Captura solicitada')
                                    ->boolean()
                                    ->trueIcon('heroicon-s-camera')
                                    ->falseIcon('heroicon-o-camera')
                                    ->trueColor('success')
                                    ->falseColor('gray'),
                            ]),
                        TextEntry::make('attachments_links')
                            ->label('Adjuntos')
                            ->html()
                            ->getStateUsing(function (TaxistaTicket $record): string {
                                $links = self::resolveAttachmentLinks($record);

                                if ($links === []) {
                                    return '-';
                                }

                                return implode('<br>', $links);
                            })
                            ->visible(fn (TaxistaTicket $record): bool => self::resolveAttachmentLinks($record) !== []),
                        TextEntry::make('description')
                            ->label('Descripción')
                            ->placeholder('Sin descripción')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    private static function resolveDocumentStoragePath(TaxistaTicket $record): ?string
    {
        $attachment = $record->attachments;

        if (is_array($attachment)) {
            $attachment = $attachment[0] ?? null;
        }

        $path = trim((string) $attachment);

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

    /**
     * @return array<int, string>
     */
    private static function resolveAttachmentLinks(TaxistaTicket $record): array
    {
        $paths = $record->attachments ?? [];
        $names = $record->attachment_file_names ?? [];

        if (! is_array($paths) || $paths === []) {
            $path = self::resolveDocumentPublicUrl($record);

            if (! $path) {
                return [];
            }

            $safeUrl = e($path);

            return ["<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\">Abrir archivo</a>"];
        }

        $links = [];

        foreach ($paths as $index => $path) {
            if (! is_string($path) || trim($path) === '') {
                continue;
            }

            $url = self::resolveDocumentPublicUrlFromPath($path);

            if (! $url) {
                continue;
            }

            $name = $names[$path] ?? $names[$index] ?? basename($path);
            $safeUrl = e($url);
            $safeName = e((string) $name);

            $links[] = "<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\">{$safeName}</a>";
        }

        return $links;
    }

    private static function resolveDocumentPublicUrlFromPath(string $path): ?string
    {
        $path = trim($path);

        if ($path === '') {
            return null;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
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

    private static function resolveDocumentPublicUrl(TaxistaTicket $record): ?string
    {
        $path = self::resolveDocumentStoragePath($record);

        if (! $path) {
            return null;
        }

        return self::resolveDocumentPublicUrlFromPath($path);
    }

    private static function resolveNotes(TaxistaTicket $record): string
    {
        $notes = data_get($record->description, 'description');

        if (is_array($notes)) {
            return json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        if (filled($notes)) {
            return (string) $notes;
        }

        if (filled($record->description)) {
            return json_encode($record->description, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        return '-';
    }
}
