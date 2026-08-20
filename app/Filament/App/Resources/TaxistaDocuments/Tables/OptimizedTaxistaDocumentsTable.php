<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Tables;

use App\Enums\PagoEstado;
use App\Models\TaxiCentral\DocumentType;
use App\Models\Taxista;
use App\Models\TaxistaDocument;
use App\Support\PortalTaxistaContext;
use App\Support\TaxistaDocumentTypes;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class OptimizedTaxistaDocumentsTable
{
    public static function configure(Table $table): Table
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return $table
                // QUERY OPTIMIZADA: select específico
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->with([
                    'department:id,name,color'
                ]))
                ->columns([
                    Stack::make([
                        TextColumn::make('title')
                            ->label('Documento')
                            ->weight(FontWeight::SemiBold)
                            ->searchable()
                            ->limit(60),

                        TextColumn::make('uploaded_at')
                            ->label('Subido')
                            ->formatStateUsing(fn(mixed $state, TaxistaDocument $record): string => ($record->uploaded_at ?? $record->created_at)?->format('d/m/Y H:i') ?? '-')
                            ->color('gray'),

                        SelectColumn::make('document_type')
                            ->label('Tipo')
                            ->options(TaxistaDocumentTypes::options())
                            ->selectablePlaceholder(false)
                            ->sortable(),

                        TextColumn::make('meta.reference')
                            ->label('Referencia')
                            ->badge()
                            ->formatStateUsing(fn(?string $state): string => filled($state) ? $state : 'Sin referencia')
                            ->color('gray'),
                    ])->space(1),

                    TextColumn::make('status')
                        ->label('Estado')
                        ->badge()
                        ->color(fn(?string $state): string => $state === 'activo' ? 'success' : 'gray'),
                ])
                // FILTERS OPTIMIZADOS: cache para evitar N+1
                ->filters([
                    SelectFilter::make('taxista_user_id')
                        ->label('Taxista')
                        ->options(fn(): array => Cache::remember('taxista_document_options', now()->addHours(2), function() {
                            return Taxista::where('status', 'active')
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->toArray();
                        })),
                        
                    SelectFilter::make('document_type')
                        ->label('Tipo')
                        ->options(TaxistaDocumentTypes::options()),
                        
                    SelectFilter::make('status')
                        ->label('Estado')
                        ->options([
                            'activo' => 'Activo',
                            'archivado' => 'Archivado',
                        ]),
                ])
                ->defaultSort('uploaded_at', 'desc')
                ->contentGrid([
                    'sm' => 1,
                ])
                ->selectable(false)
                ->recordActions([
                    ActionGroup::make([
                        EditAction::make(),
                        DeleteAction::make(),
                    ])->color('gray'),
                ])
                ->toolbarActions([
                    BulkActionGroup::make([
                        DeleteBulkAction::make(),
                    ]),
                ]);
        }

        return $table
            // QUERY OPTIMIZADA para admin
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with([
                'taxista:id,name',
                'department:id,name,color'
            ]))
            ->columns([
                TextColumn::make('taxista.name')
                    ->label('Taxista')
                    ->searchable()
                    ->sortable()
                    ->visible(fn(): bool => !PortalTaxistaContext::isPortalPanel()),

                TextColumn::make('title')
                    ->label('Documento')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge()
                    ->color('info')
                    ->formatStateUsing(fn(string $state): string => TaxistaDocumentTypes::getLabel($state)),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge()
                    ->color(fn($record): string => $record->department?->color ?? 'gray')
                    ->placeholder('Sin departamento'),

                TextColumn::make('uploaded_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(?string $state): string => $state === 'activo' ? 'success' : 'gray'),

                TextColumn::make('meta.reference')
                    ->label('Referencia')
                    ->badge()
                    ->formatStateUsing(fn(?string $state): string => filled($state) ? $state : 'Sin referencia')
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            // FILTERS OPTIMIZADOS: cache para evitar N+1
            ->filters([
                SelectFilter::make('taxista_user_id')
                    ->label('Taxista')
                    ->options(fn(): array => Cache::remember('taxista_document_options', now()->addHours(2), function() {
                        return Taxista::where('status', 'active')
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })),

                SelectFilter::make('document_type')
                    ->label('Tipo')
                    ->options(TaxistaDocumentTypes::options()),

                SelectFilter::make('department_id')
                    ->label('Departamento')
                    ->options(fn(): array => Cache::remember('department_document_options', now()->addHours(2), function() {
                        return \App\Models\BookingDepartment::where('status', true)
                            ->orderBy('name')
                            ->pluck('name', 'id')
                            ->toArray();
                    })),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'activo' => 'Activo',
                        'archivado' => 'Archivado',
                    ]),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->recordActions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
