<?php

namespace App\Filament\App\Resources\TaxistaTickets\Tables;

use App\Models\Taxista;
use App\Support\PortalTaxistaContext;
use Filament\Actions\ActionGroup;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Guava\FilamentIconSelectColumn\Tables\Columns\IconSelectColumn;
use App\Enums\TicketStatus;
use App\Models\TaxistaTicket;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TaxistaTicketsTable
{
    public static function configure(Table $table): Table
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return $table
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['department','user']))
                ->columns([
                    Stack::make([
                        TextColumn::make('id')
                            ->label('ID')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),
                        TextColumn::make('title')
                            ->label('Titulo')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),

                        TextColumn::make('department.name')
                            ->label('Departamento')
                            ->badge()
                            ->placeholder('Sin departamento')
                            ->toggleable(isToggledHiddenByDefault: true)
                            ->sortable(),
                        TextColumn::make('ticket_type')
                            ->label('Tipo de Ticket')
                            ->badge()
                            ->toggleable(isToggledHiddenByDefault: false)
                            ->sortable(),
                        TextColumn::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'baja' => 'gray',
                                'media' => 'info',
                                'alta' => 'warning',
                                'urgente' => 'danger',
                                default => 'gray',
                            })
                            ->toggleable(isToggledHiddenByDefault: true)
                            ->sortable(),
                        TextColumn::make('priority')
                            ->label('Prioridad')
                            ->badge()
                            ->color(fn(string $state): string => match ($state) {
                                'baja' => 'gray',
                                'media' => 'info',
                                'alta' => 'warning',
                                'urgente' => 'danger',
                                default => 'gray',
                            })
                            ->toggleable(isToggledHiddenByDefault: true)
                            ->sortable(),
                    ])->space(1),

                    IconSelectColumn::make('status')
                        ->label('Estado')
                        ->options(TicketStatus::class)
                        ->sortable(),
                ])
                ->filters([
                    SelectFilter::make('user_id')
                        ->label('Usuario')
                        ->relationship('user', 'name')
                        ->searchable()
                        //->hiddenOn([App\Filament\Clusters\Taxistas\Taxistas\RelationManagers\TicketsRelationManager::class])
                        ->preload(),

                    SelectFilter::make('priority')
                        ->label('Prioridad')
                        ->options([
                            'baja' => 'Baja',
                            'media' => 'Media',
                            'alta' => 'Alta',
                            'urgente' => 'Urgente',
                        ]),
                    SelectFilter::make('status')
                        ->label('Estado')
                        ->options([
                            'abierto' => 'Abierto',
                            'en_proceso' => 'En proceso',
                            'resuelto' => 'Resuelto',
                            'cerrado' => 'Cerrado',
                        ]),
                ])
                ->defaultSort('opened_at', 'desc')
                ->recordActions([
                     EditAction::make()
                     ->label('Editar')
                     ->iconButton(),
                     Action::make('preview')
                            ->label('')
                            ->tooltip('Ver fichero')
                            ->icon('heroicon-o-eye')
                            ->color('gray')
                            ->iconSize('xl')
                            ->url(fn(TaxistaTicket $record): ?string => self::resolvePreviewUrl($record), shouldOpenInNewTab: true)
                            ->extraAttributes(['x-on:click.stop' => ''])
                            ->visible(fn(TaxistaTicket $record): bool => filled(self::resolvePreviewUrl($record))),

                    ActionGroup::make([
                        DeleteAction::make(),
                                                Action::make('download')
                            ->label('')
                            ->tooltip('Descargar')
                            ->iconSize('xl')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->extraAttributes(['x-on:click.stop' => ''])
                            ->action(function (TaxistaTicket $record) {
                                $path = self::resolveDownloadPath($record);
                                $disk = self::resolveDownloadDisk($record, $path);

                                if (!$path || !$disk || !Storage::disk($disk)->exists($path)) {
                                    Notification::make()
                                        ->title('Archivo no disponible')
                                        ->danger()
                                        ->send();

                                    return null;
                                }

                                Notification::make()
                                    ->title('Descarga iniciada')
                                    ->success()
                                    ->send();

                                return Storage::disk($disk)->download(
                                    $path,
                                    self::resolveDownloadFilename($record, $path),
                                );
                            })
                            ->visible(fn(TaxistaTicket $record): bool => filled(self::resolveDownloadPath($record))),
                    ])->color('gray'),
                ])
                ->toolbarActions([
                        DeleteBulkAction::make(),
                        BulkAction::make('pendiente')
                            ->label('Pendiente')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'pendiente']);
                                });

                                Notification::make()
                                    ->title('Ticket pendiente')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('en_proceso')
                            ->label('En Proceso')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'en_proceso']);
                                });

                                Notification::make()
                                    ->title('Ticket en proceso')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('finalizada')
                            ->label('Finalizada')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'finalizada']);
                                });

                                Notification::make()
                                    ->title('Ticket finalizado')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('cancelada')
                            ->label('Cancelada')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'cancelada']);
                                });

                                Notification::make()
                                    ->title('Ticket cancelado')
                                    ->success()
                                    ->send();
                            }),
                ]);
        }

        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['user', 'department', 'assignedTo']))
            ->columns([
                TextColumn::make('id')
                            ->label('ID')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->sortable(),
                TextColumn::make('title')
                    ->label('Titulo')
                    ->weight(FontWeight::SemiBold)
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('ticket_type')
                    ->label('Tipo de Ticket')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('priority')
                    ->label('Prioridad')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'baja' => 'gray',
                        'media' => 'info',
                        'alta' => 'warning',
                        'urgente' => 'danger',
                        default => 'gray',
                    }),

                IconSelectColumn::make('status')
                    ->label('Estado')
                    ->options(TicketStatus::class),

                /*TextColumn::make('assignedTo.name')
                    ->label('Asignado')
                    ->toggleable(),*/

                TextColumn::make('due_at')
                    ->label('Vence')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('Taxista')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('priority')
                    ->label('Prioridad')
                    ->options([
                        'baja' => 'Baja',
                        'media' => 'Media',
                        'alta' => 'Alta',
                        'urgente' => 'Urgente',
                    ]),
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'abierto' => 'Abierto',
                        'en_proceso' => 'En proceso',
                        'resuelto' => 'Resuelto',
                        'cerrado' => 'Cerrado',
                    ]),
            ])
            ->defaultSort('opened_at', 'desc')
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
            ])
            ->recordActions([
                                     EditAction::make()
                     ->label('Editar')
                     ->iconButton(),
                     Action::make('preview')
                            ->label('')
                            ->tooltip('Ver fichero')
                            ->icon('heroicon-o-eye')
                            ->color('gray')
                            ->iconSize('xl')
                            ->url(fn(TaxistaTicket $record): ?string => self::resolvePreviewUrl($record), shouldOpenInNewTab: true)
                            ->extraAttributes(['x-on:click.stop' => ''])
                            ->visible(fn(TaxistaTicket $record): bool => filled(self::resolvePreviewUrl($record))),
DeleteAction::make(),
                        Action::make('download')
                            ->label('')
                            ->tooltip('Descargar')
                            ->iconSize('xl')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->extraAttributes(['x-on:click.stop' => ''])
                            ->action(function (TaxistaTicket $record) {
                                $path = self::resolveDownloadPath($record);
                                $disk = self::resolveDownloadDisk($record, $path);

                                if (!$path || !$disk || !Storage::disk($disk)->exists($path)) {
                                    Notification::make()
                                        ->title('Archivo no disponible')
                                        ->danger()
                                        ->send();

                                    return null;
                                }

                                Notification::make()
                                    ->title('Descarga iniciada')
                                    ->success()
                                    ->send();

                                return Storage::disk($disk)->download(
                                    $path,
                                    self::resolveDownloadFilename($record, $path),
                                );
                            })
                            ->visible(fn(TaxistaTicket $record): bool => filled(self::resolveDownloadPath($record))),
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ActionGroup::make([
                        BulkAction::make('abierto')
                            ->label('Abierto')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'abierto']);
                                });

                                Notification::make()
                                    ->title('Ticket abierto')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('en_proceso')
                            ->label('En Proceso')
                            ->icon('heroicon-m-check-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'en_proceso']);
                                });

                                Notification::make()
                                    ->title('Ticket en proceso')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('resuelto')
                            ->label('Resuelto')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'resuelto']);
                                });

                                Notification::make()
                                    ->title('Ticket resuelto')
                                    ->success()
                                    ->send();
                            }),
                        BulkAction::make('cancelado')
                            ->label('Cancelado')
                            ->icon('heroicon-m-x-circle')
                            ->requiresConfirmation()
                            ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                                $records->each(function ($record) {
                                    $record->update(['status' => 'cancelado']);
                                });

                                Notification::make()
                                    ->title('Ticket cancelado')
                                    ->success()
                                    ->send();
                            }),
                        
                ])
                ]),
            ]);

            }

    private static function resolveAttachmentLinks(TaxistaTicket $record): array
    {
        $paths = $record->attachments ?? [];
        $names = $record->attachment_file_names ?? [];

        if (!is_array($paths) || $paths === []) {
            $path = self::resolveDocumentPublicUrl($record);

            if (!$path) {
                return [];
            }

            $safeUrl = e($path);

            return ["<a href=\"{$safeUrl}\" target=\"_blank\" rel=\"noopener noreferrer\">Abrir archivo</a>"];
        }

        $links = [];

        foreach ($paths as $index => $path) {
            if (!is_string($path) || trim($path) === '') {
                continue;
            }

            $url = self::resolveDocumentPublicUrlFromPath($path);

            if (!$url) {
                continue;
            }

            $name = $names[$path] ?? $names[$index] ?? basename($path);
            $safeUrl = e($url);
            $safeName = e((string)$name);

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
            $baseUrl = (string)config('filesystems.disks.documentos.url');

            if ($baseUrl !== '') {
                return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
            }
        }

        $publicPath = Str::startsWith($path, 'documentos/') ? $path : 'documentos/' . $path;

        $publicBaseUrl = (string)config('filesystems.disks.public.url');

        if (Storage::disk('public')->exists($publicPath) && $publicBaseUrl !== '') {
            return rtrim($publicBaseUrl, '/') . '/' . ltrim($publicPath, '/');
        }

        if (Storage::disk('public')->exists($path) && $publicBaseUrl !== '') {
            return rtrim($publicBaseUrl, '/') . '/' . ltrim($path, '/');
        }

        return null;
    }

    private static function resolveDocumentPublicUrl(TaxistaTicket $record): ?string
    {
        $path = self::resolveDocumentStoragePath($record);

        if (!$path) {
            return null;
        }

        return self::resolveDocumentPublicUrlFromPath($path);
    }

    private static function resolveDocumentStoragePath(TaxistaTicket $record): ?string
    {
        $attachment = $record->attachments;

        if (is_array($attachment)) {
            $attachment = $attachment[0] ?? null;
        }

        $path = trim((string)$attachment);

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

    private static function resolvePreviewUrl(TaxistaTicket $record): ?string
    {
        return self::resolveDocumentPublicUrl($record);
    }

    private static function resolveDownloadPath(TaxistaTicket $record): ?string
    {
        $path = self::resolveDocumentStoragePath($record);

        if (!$path) {
            return null;
        }

        if (Storage::disk('documentos')->exists($path)) {
            return $path;
        }

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $publicPath = 'documentos/' . $path;

        return Storage::disk('public')->exists($publicPath) ? $publicPath : null;
    }

    private static function resolveDownloadDisk(TaxistaTicket $record, ?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        if (Storage::disk('documentos')->exists($path)) {
            return 'documentos';
        }

        if (Storage::disk('public')->exists($path)) {
            return 'public';
        }

        return null;
    }

    private static function resolveDownloadFilename(TaxistaTicket $record, string $path): string
    {
        $fileName = basename($path);
        $titleSlug = Str::slug($record->title, '_');

        if ($titleSlug === '') {
            return $fileName;
        }

        $extension = pathinfo($fileName, PATHINFO_EXTENSION);

        return $extension === ''
            ? $titleSlug
            : $titleSlug . '.' . $extension;
    }

    private static function resolveNotes(TaxistaTicket $record): string
    {
        $notes = data_get($record->description, 'description');

        if (is_array($notes)) {
            return json_encode($notes, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        if (filled($notes)) {
            return (string)$notes;
        }

        if (filled($record->description)) {
            return json_encode($record->description, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) ?: '-';
        }

        return '-';
    }
}
