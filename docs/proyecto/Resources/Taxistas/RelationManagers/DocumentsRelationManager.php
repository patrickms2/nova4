<?php

namespace App\Filament\App\Resources\Taxistas\RelationManagers;

use App\Services\Taxistas\TaxistaDocumentMultipageImporter;
use App\Support\TaxistaDocumentTypes;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    protected static ?string $title = 'Documentos';

    protected static ?string $recordTitleAttribute = 'title';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Hidden::make('taxista_user_id')
                    ->default(fn (): int => (int) $this->getOwnerRecord()->id)
                    ->required(),

                Hidden::make('uploaded_by_user_id')
                    ->default(fn (): ?int => auth()->id()),

                Section::make('Documento')
                    ->schema([
                        TextInput::make('title')
                            ->label('Titulo')
                            ->required()
                            ->maxLength(255),

                        Select::make('booking_department_id')
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
                            ->preload(),

                        Select::make('document_type')
                            ->label('Tipo')
                            ->required()
                            ->options(TaxistaDocumentTypes::options()),

                        FileUpload::make('file_path')
                            ->label('Archivo')
                            ->disk('public')
                            ->directory('taxistas/documents')
                            ->acceptedFileTypes(['application/pdf', 'image/*', '.doc', '.docx', '.zip'])
                            ->required(),

                        Select::make('status')
                            ->label('Estado')
                            ->options([
                                'activo' => 'Activo',
                                'archivado' => 'Archivado',
                            ])
                            ->default('activo')
                            ->required(),

                        Toggle::make('is_favorite')
                            ->label('Favorito')
                            ->default(false),
                    ])
                    ->columns(2),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(),

                TextColumn::make('document_type')
                    ->label('Tipo')
                    ->badge(),

                TextColumn::make('department.name')
                    ->label('Departamento')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'activo' ? 'success' : 'gray'),

                IconColumn::make('is_favorite')
                    ->label('Favorito')
                    ->boolean(),

                TextColumn::make('uploaded_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->toggleable(),
            ])
            ->filters([
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
            ->headerActions([
                ActionGroup::make([
                    $this->makeMultipageImportAction('agencias_multipagina', 'Agencias', 'agencias'),
                    $this->makeMultipageImportAction('cuotas_multipagina', 'Cuotas', 'cuotas'),
                    $this->makeMultipageImportAction('nominas_multipagina', 'Nominas', 'nomina'),
                    $this->makeMultipageImportAction('repuestos_multipagina', 'Repuestos', 'repuestos'),
                    $this->makeMultipageImportAction('seguros_multipagina', 'Seguros', 'seguro'),
                ])
                    ->label('Multipagina')
                    ->icon('heroicon-s-squares-plus')
                    ->color('warning'),
                CreateAction::make()
                    ->label('Subir documento')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['taxista_user_id'] = (int) $this->getOwnerRecord()->id;
                        $data['uploaded_by_user_id'] = auth()->id();
                        $data['uploaded_at'] = $data['uploaded_at'] ?? now()->toDateTimeString();

                        return $data;
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    private function makeMultipageImportAction(string $name, string $label, string $documentType): Action
    {
        return Action::make($name)
            ->label($label)
            ->icon('heroicon-s-squares-plus')
            ->color('warning')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->form([
                Toggle::make('skip_duplicates')
                    ->label('Omitir duplicados (tipo + referencia)')
                    ->default(true),

                FileUpload::make('pdf')
                    ->label("PDF multipagina ({$label})")
                    ->disk('local')
                    ->directory('imports/taxista-documents/multipage')
                    ->acceptedFileTypes(['application/pdf'])
                    ->preserveFilenames()
                    ->previewable(false)
                    ->required()
                    ->reactive()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function (mixed $state, Set $set, Get $get) use ($documentType): void {
                        if ($state === null || is_string($state)) {
                            $pdfPath = is_string($state) && $state !== ''
                                ? storage_path('app/'.ltrim($state, '/'))
                                : null;
                        } else {
                            $pdfPath = method_exists($state, 'getPathname') ? $state->getPathname() : null;
                        }

                        if (! $pdfPath || ! is_file($pdfPath)) {
                            return;
                        }

                        $importer = app(TaxistaDocumentMultipageImporter::class);
                        $summary = $importer->importFromPdfPath(
                            pdfPath: $pdfPath,
                            documentType: $documentType,
                            uploadedByUserId: auth()->id(),
                            skipDuplicates: (bool) $get('skip_duplicates'),
                            forcedTaxistaUserId: (int) $this->getOwnerRecord()->id,
                        );

                        $logMessage = $summary['log_path'] !== ''
                            ? "\n\nLog: storage/app/{$summary['log_path']}"
                            : '';

                        $set('notes', $summary['notes'] . $logMessage);

                        $notification = Notification::make()
                            ->title('Importacion multipagina completada')
                            ->body($summary['notes'] . $logMessage);

                        if ($summary['errors'] !== []) {
                            $notification->warning();
                        } else {
                            $notification->success();
                        }

                        $notification->send();
                    }),

                Textarea::make('notes')
                    ->label('Notas / incidencias')
                    ->rows(4)
                    ->dehydrated(false)
                    ->placeholder('Sube un PDF para procesar...'),
            ]);
    }
}
