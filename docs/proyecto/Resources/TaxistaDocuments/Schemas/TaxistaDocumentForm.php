<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Schemas;

use App\Support\PortalTaxistaContext;
use App\Support\TaxistaDocumentTypes;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class TaxistaDocumentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('uploaded_by_user_id')
                    ->default(fn(): ?int => auth()->id()),

                Section::make('Documento taxi')
                    ->description('Sube el archivo y clasifica el documento para encontrarlo rapido.')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('taxista_user_id')
                            ->label('Taxista')
                            ->relationship(
                                'taxista',
                                'name',
                                modifyQueryUsing: fn(Builder $query): Builder => PortalTaxistaContext::scopeTaxistaOptions($query)
                            )
                            ->default(fn(): ?int => PortalTaxistaContext::taxistaUserId())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->columnSpanFull()
                            ->required()
                            ->visible(fn(): bool => !PortalTaxistaContext::isPortalPanel()),


                        /* Select::make('booking_department_id')
                             ->label('Departamento')
                             ->relationship(
                                 'department',
                                 'name',
                                 modifyQueryUsing: fn (Builder $query): Builder => $query
                                     ->where('is_active', true)
                                     ->where('has_documents_service', true)
                                     ->orderBy('name')
                             )
                             ->searchable()
                             ->preload()
                             ->visible(fn (): bool => ! PortalTaxistaContext::isPortalPanel()),*/
                        Grid::make([
                            'default' => 1,
                            'md' => 2,
                        ])
                            ->columnSpanFull()
                            ->schema([


                                Toggle::make('is_favorite')
                                    ->label('Favorito')
                                    ->columnSpan(1)
                                    ->default(false),

                                Toggle::make('show_advanced')
                                    ->label('Editar campos, sino lo lee del documento')
                                    ->live()
                                    ->dehydrated(false)
                                    ->default(false)
                                    ->columnSpan(1),
                                TextInput::make('title')
                                    ->label('Titulo')
                                    ->required(fn(Get $get): bool => (bool) $get('show_advanced'))
                                    ->live()
                                    ->maxLength(255)
                                    ->visible(fn(Get $get): bool => (bool)$get('show_advanced'))
                                    ->dehydratedWhenHidden(),

                                Select::make('document_type')
                                    ->label('Tipo de documento')
                                    ->required(fn(Get $get): bool => (bool) $get('show_advanced'))
                                    ->options(TaxistaDocumentTypes::options())
                                    ->live()
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                        $set('title', $state);
                                    })
                                    ->visible(fn(Get $get): bool => (bool)$get('show_advanced')),

                                Textarea::make('notas')
                                    ->label('Notas')
                                    ->rows(3)
                                    ->columnSpanFull()
                                    ->visible(fn(Get $get): bool => (bool)$get('show_advanced')),
                                TextInput::make('meta.reference')
                                    ->label('Referencia')
                                    ->placeholder('Ej: factura, matricula o codigo interno')
                                    ->maxLength(255)
                                    ->columnSpan(1)
                                    ->visible(fn(Get $get): bool => (bool)$get('show_advanced')),

                                Select::make('status')
                                    ->label('Estado')
                                    ->required(fn(Get $get): bool => (bool) $get('show_advanced'))
                                    ->default('activo')
                                    ->options([
                                        'activo' => 'Activo',
                                        'archivado' => 'Archivado',
                                    ])
                                    ->visible(fn(Get $get): bool => (bool)$get('show_advanced')),

                                FileUpload::make('file_path')
                                    ->label('Archivo')
                                    ->helperText('Formatos permitidos: PDF o ZIP. Tamano maximo recomendado: 5 MB.')
                                    ->disk('public')
                                    ->preserveFilenames()
                                    ->directory('taxistas/documents')
                                    ->acceptedFileTypes([
                                        'application/pdf',
                                        'application/zip',
                                        '.zip',
                                    ])
                                    ->maxSize(5120)
                                    ->required()
                                    ->visible(fn(Get $get): bool => filled($get('taxista_user_id')))
                                    ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                        if (! $state instanceof TemporaryUploadedFile) {
                                            return;
                                        }

                                        $originalName = pathinfo($state->getClientOriginalName(), PATHINFO_FILENAME);

                                        if (blank($get('title'))) {
                                            $set('title', str_replace(['-', '_'], ' ', $originalName));
                                        }
                                    })
                                    ->columnSpanFull(),
                            ]),

                    ])
                    ->columns(2),


            ]);
    }
}
