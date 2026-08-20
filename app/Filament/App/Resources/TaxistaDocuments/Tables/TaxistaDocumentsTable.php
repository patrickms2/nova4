<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Tables;

use App\Enums\PagoEstado;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentInfolist;
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
use Filament\Actions\ViewAction;
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
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class TaxistaDocumentsTable
{
    public static function configure(Table $table): Table
    {
        if (PortalTaxistaContext::isPortalPanel()) {
            return $table
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['department:id,name']))
                ->columns([
                    Stack::make([
                        TextColumn::make('title')
                            ->label('Documento')
                            ->weight(FontWeight::SemiBold)
                            ->searchable(query: fn (Builder $query, string $search): Builder => self::applyDocumentSearch($query, $search)),

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
                ->defaultSort('uploaded_at', 'desc')
                ->contentGrid([
                    'sm' => 1,
                ])
                ->selectable(false)
                ->recordActions([
                    Action::make('toggleFavorite')
                        ->hiddenLabel()
                        ->tooltip(fn(TaxistaDocument $record): string => $record->is_favorite ? 'Quitar favorito' : 'Marcar favorito')
                        ->icon(fn(TaxistaDocument $record): string => $record->is_favorite ? 'heroicon-s-star' : 'heroicon-o-star')
                        ->color(fn(TaxistaDocument $record): string => $record->is_favorite ? 'warning' : 'gray')
                        ->action(fn(TaxistaDocument $record): bool => $record->update(['is_favorite' => !$record->is_favorite])),
                    Action::make('openFile')
                        ->hiddenLabel()
                        ->tooltip('Ver archivo')
                        ->icon('heroicon-o-eye')
                        ->color('gray')
                        ->url(fn(TaxistaDocument $record): ?string => self::resolveDocumentPublicUrl($record))
                        ->openUrlInNewTab()
                        ->visible(fn(TaxistaDocument $record): bool => filled(self::resolveDocumentPublicUrl($record))),
                    Action::make('downloadFile')
                        ->hiddenLabel()
                        ->tooltip('Descargar archivo')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('gray')
                        ->action(function (TaxistaDocument $record) {
                            $path = self::resolveDocumentStoragePath($record);

                            if (!$path || !Storage::disk('public')->exists($path)) {
                                Notification::make()
                                    ->title('Archivo no disponible')
                                    ->danger()
                                    ->send();

                                return null;
                            }

                            return Storage::disk('public')->download(
                                $path,
                                self::resolveDocumentFilename($record, $path),
                            );
                        }),
                    ViewAction::make()
                        ->hiddenLabel()
                        ->tooltip('Ver documento')
                        ->icon('heroicon-o-document-text')
                        ->color('gray')
                        ->slideOver()
                        ->modalWidth('3xl')
                        ->schema(fn(Schema $schema): Schema => TaxistaDocumentInfolist::configure($schema)),
                    EditAction::make()
                        ->hiddenLabel()
                        ->tooltip('Editar documento')
                        ->icon('heroicon-o-pencil-square')
                        ->color('gray')
                        ->slideOver()
                        ->schema(fn(Schema $schema): Schema => TaxistaDocumentForm::configure($schema))
                        ->successNotificationTitle('Documento actualizado'),
                    ActionGroup::make([
                        DeleteAction::make(),
                    ])
                        ->hiddenLabel()
                        ->tooltip('Acciones')
                        ->icon('heroicon-o-ellipsis-horizontal')
                        ->color('gray'),
                ]);
        }

        return $table
            ->modifyQueryUsing(fn(Builder $query): Builder => $query->with(['taxista', 'department', 'uploadedBy']))
            ->columns([
                TextColumn::make('taxista.name')
                    ->label('Taxista')
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::applyDocumentSearch($query, $search))
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('title')
                    ->label('Titulo')
                    ->searchable(query: fn (Builder $query, string $search): Builder => self::applyDocumentSearch($query, $search)),

                SelectColumn::make('document_type')
                    ->label('Tipo')
                    ->options(TaxistaDocumentTypes::options())
                    ->selectablePlaceholder(false)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(?string $state): string => $state === 'activo' ? 'success' : 'gray')
                    ->toggleable(isToggledHiddenByDefault: true),

                IconColumn::make('is_favorite')
                    ->label('Favorito')
                    ->boolean()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('uploadedBy.name')
                    ->label('Subido por')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('uploaded_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                SelectFilter::make('taxista_user_id')
                    ->label('Taxista')
                    ->searchable()
                    ->options(Taxista::query()->orderBy('name')->pluck('name', 'id')->toArray()),
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
                'md' => 2,
                '2xl' => 3,
            ])
            ->recordActions([
                EditAction::make('edit')
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->extraModalFooterActions(function (TaxistaDocument $record, EditAction $action): array {
                        return [
                            Action::make('zip')
                                ->label('ZIP')
                                ->color('primary')
                                ->modalWidth(Width::ExtraLarge)
                                ->modalSubmitActionLabel('Procesar ZIP')
                                ->modalCancelActionLabel('Cancelar')
                                ->icon('heroicon-s-archive-box-arrow-down')
                                ->modalSubmitAction(false)
                                ->form(fn(Schema $form) => [
                                    FileUpload::make('zip_file')
                                        ->label('Archivo ZIP')
                                        ->required()
                                        ->acceptedFileTypes(['application/zip'])
                                        ->disk('public')
                                        ->directory('temp_zips')
                                        ->preserveFilenames()
                                        ->previewable(false)
                                        ->helperText('Solo se permiten archivos ZIP con PDFs dentro'),
                                ])
                                ->action(function (array $data) {
                                    $zipFile = $data['zip_file'];
                                    $zipPath = storage_path('app/public/' . $zipFile);

                                    if (!file_exists($zipPath)) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('Archivo ZIP no encontrado')
                                            ->danger()
                                            ->send();
                                        return;
                                    }

                                    $zip = new ZipArchive;
                                    if ($zip->open($zipPath) === true) {
                                        $extractPath = storage_path('app/tmp_uploads/' . uniqid('zip_', true));
                                        if (!is_dir($extractPath)) {
                                            mkdir($extractPath, 0777, true);
                                        }
                                        $zip->extractTo($extractPath);
                                        $zip->close();

                                        $iterator = new RecursiveIteratorIterator(
                                            new RecursiveDirectoryIterator($extractPath)
                                        );

                                        $totalPdfs = 0;
                                        $conNif = 0;
                                        $asociados = 0;
                                        $sinAsociar = 0;
                                        $logRows = [];

                                        foreach ($iterator as $file) {
                                            if ($file->isDir()) continue;
                                            if (str_starts_with($file->getFilename(), '.')) continue;
                                            if (mb_strtolower($file->getExtension()) !== 'pdf') continue;

                                            $totalPdfs++;
                                            $pdfFile = $file->getPathname();
                                            $pdfName = $file->getFilename();
                                            $nif = null;
                                            $issues = [];

                                            // --- Detectar NIF en nombre ---
                                            if (preg_match('/\b(\d{8})([A-Za-z])\b/', $pdfName, $matches)) {
                                                $numero = (int)$matches[1];
                                                $letra = mb_strtoupper($matches[2]);
                                                $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
                                                $letraCorrecta = $letras[$numero % 23];
                                                if ($letra === $letraCorrecta) {
                                                    $nif = $numero . $letra;
                                                }
                                            }

                                            // --- Si no hay NIF en nombre: leer contenido PDF ---
                                            if (!$nif) {
                                                try {
                                                    $textoPdf = TaxistaDocument::getText($pdfFile);
                                                    if (preg_match('/\b(\d{8})([A-Za-z])\b/', $textoPdf, $m2)) {
                                                        $numero = (int)$m2[1];
                                                        $letra = mb_strtoupper($m2[2]);
                                                        $letras = 'TRWAGMYFPDXBNJZSQVHLCKE';
                                                        $letraCorrecta = $letras[$numero % 23];
                                                        if ($letra === $letraCorrecta) {
                                                            $nif = $numero . $letra;
                                                        }
                                                    }
                                                } catch (Throwable $e) {
                                                    $issues[] = 'Contenido no legible';
                                                }
                                            }

                                            if ($nif) {
                                                $conNif++;
                                                $taxista = Taxista::where('nif', $nif)->first();
                                                if ($taxista) {
                                                    $asociados++;
                                                } else {
                                                    $sinAsociar++;
                                                    $issues[] = 'Taxista no encontrado para NIF: ' . $nif;
                                                }
                                            } else {
                                                $sinAsociar++;
                                                $issues[] = 'NIF no detectado';
                                            }

                                            // Guardar incidencias
                                            if (!empty($issues)) {
                                                $logRows[] = [
                                                    $pdfName,
                                                    $nif ?? '',
                                                    $taxista->id ?? '',
                                                    implode('; ', $issues),
                                                ];
                                            }
                                        }

                                        // Limpiar directorio temporal
                                        \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

                                        // Mostrar reporte
                                        $reporte = "
                                        📊 **Reporte de Procesamiento ZIP**

                                        📁 **Total PDFs encontrados:** {$totalPdfs}
                                        🔍 **Con NIF detectado:** {$conNif}
                                        ✅ **Asociados a taxistas:** {$asociados}
                                        ❌ **Sin asociar:** {$sinAsociar}
                                        ";

                                        if (!empty($logRows)) {
                                            $reporte .= "\n\n⚠️ **Incidencias:**\n";
                                            foreach ($logRows as $row) {
                                                $reporte .= "- {$row[0]}: {$row[3]}\n";
                                            }
                                        }

                                        Notification::make()
                                            ->title('Procesamiento ZIP Completado')
                                            ->body($reporte)
                                            ->success()
                                            ->persistent()
                                            ->send();

                                    } else {
                                        Notification::make()
                                            ->title('Error')
                                            ->body('No se pudo abrir el archivo ZIP')
                                            ->danger()
                                            ->send();
                                    }
                                }),
                            Action::make('carpeta')
                                ->label('Subir ZIP')
                                ->color('warning')
                                ->modalWidth(Width::ExtraLarge)
                                ->modalSubmitActionLabel('Subir ZIP')
                                ->modalCancelActionLabel('Enviar')
                                ->icon('heroicon-s-squares-plus')
                                ->modalSubmitAction(false)
                                ->form(fn(Schema $form) => [

                                    Select::make('type_id')
                                        ->label('Tipo Doc.')
                                        ->required()
                                        ->live()
                                        ->native(false)
                                        ->createOptionForm([
                                            TextInput::make('name')
                                                ->label('Nombre')
                                                ->required()
                                                ->placeholder('Tipo Document'),
                                        ])
                                        ->createOptionUsing(function (array $data, Set $set, Get $get): ?int {

                                            // Aquí creamos un nuevo registro en la tabla TipoUsuario
                                            $type_id = DocumentType::create(['name' => $data['name'], 'is_active' => 1]);
                                            $set('type_id', $type_id->id);

                                            return $type_id->id; // Retornamos el id del nuevo registro para que sea seleccionado automáticamente
                                        })
                                        ->options(function (Get $get) {
                                            return DocumentType::where('is_active', 1)->pluck('name', 'id');
                                        })
                                        ->preload()
                                        ->default(function ($state, Set $set, Get $get) {
                                            return $get('type_id');
                                        }),
                                    FileUpload::make('attachments')
                                        ->label('Fichero PDF')
                                        ->preserveFilenames()
                                        ->disk('Documents')
                                        ->acceptedFileTypes(['application/pdf'])
                                        ->imagePreviewHeight('250')
                                        ->reactive()
                                        ->preserveFilenames()
                                        ->previewable(false)
                                        ->storeFileNamesIn('attachment_file_names')
                                        ->live(onBlur: true)
                                        ->helperText('Solo se permiten archivos PDF individuales. Para procesar múltiples PDFs, usa el botón ZIP')
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            // Validar que sea PDF y no ZIP
                                            if ($state) {
                                                $originalName = $state->getClientOriginalName();
                                                $ext = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                                                if ($ext === 'zip') {
                                                    // Rechazar ZIPs
                                                    $set('attachments', null);
                                                    Notification::make()
                                                        ->title('Archivo no permitido')
                                                        ->body('Los archivos ZIP deben procesarse con el botón ZIP')
                                                        ->warning()
                                                        ->send();
                                                    return;
                                                }
                                            }

                                            // Procesamiento normal para PDFs...
                                            $originalName = $state->getClientOriginalName();
                                            $ext = mb_strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                            $originalName = pathinfo($originalName, PATHINFO_FILENAME);

                                            // === ZIP: procesar masivamente y generar log de incidencias ===
                                            if ($ext === 'zip') {
                                                // Este código ya no debería ejecutarse por la validación anterior
                                                return;
                                            }

                                            // Procesamiento normal para PDFs individuales
                                            // Continuar con el flujo normal para archivos PDF...
                                        }),
                                ]),

                            $action->getModalSubmitAction()
                                ->label('Guardar')
                                ->icon('heroicon-o-credit-card')
                                ->color('warning')
                                ->close(false)
                                ->openUrlInNewTab(false)
                                ->action(function (TaxistaDocument $record, array $data, $action) {
                                    $nuevoPagado = $record->pagado + (float)($data['pagado'] ?? 0);

                                    $updateData = [
                                        'pagado' => $nuevoPagado,
                                        'metodo_pago' => 'R',
                                        'status' => 'pendiente',
                                        'ref_pago' => $record->ref_pago,
                                        'usuario_id' => $record->usuario_id,
                                        'fecha_pago' => Carbon::now()->format('Y-m-d H:i:s'),
                                    ];

                                    if ($nuevoPagado >= $record->importe) {
                                        $updateData['status'] = PagoEstado::PAGADO;
                                    } elseif ($nuevoPagado > 0) {
                                        $updateData['status'] = PagoEstado::PAGO_PARCIAL;
                                    } else {
                                        $updateData['status'] = PagoEstado::PENDIENTE;
                                    }
                                    // Actualizar primero en BD
                                    $record->updateOrFail($updateData);

                                    Notification::make()
                                        ->title('Abriendo Redsys')
                                        ->body('Redirigiendo al TPV en una nueva pestaña...')
                                        ->success()
                                        ->send();

                                    $url = route('redsys.pay.fromPago', ['pago' => $record->id]);

                                    return redirectTo($url);
                                    $action->url(
                                        function (Pago $record) {
                                            return route('redsys.pay.fromPago', ['pago' => $record->id]);
                                        }, shouldOpenInNewTab: true);
                                    $url = route('redsys.pay.fromPago', ['pago' => $record->id]);
                                }),
                        ];
                    }
                    ),

                Action::make('viewFile')
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->url(function (TaxistaDocument $record) {
                        $attachmentPath = trim((string)($record->attachments ?? ''), '/');
                        $attachmentPath = preg_replace('#^documentos/#', '', $attachmentPath);
                        $filename = trim((string)($record->attachment_file_names ?? ''), '/');

                        if (!filled($filename)) {
                            return '#';
                        }

                        $relative = $attachmentPath !== '' ? $attachmentPath . '/' . $filename : $filename;

                        return \Illuminate\Support\Facades\Storage::disk('documentos')->url($relative);
                    })
                    ->openUrlInNewTab(),


                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])->color('gray'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('Cambiar Usuario')
                        ->icon('heroicon-m-pencil-square')
                        ->form([
                            Select::make('taxista_user_id')
                                ->label('Usuarios')
                                ->default(1)
                                ->options(Taxista::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                ->searchable()
                                ->preload()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data): void {
                            $records->each->update(['taxista_user_id' => $data['taxista_user_id']]);
                        })
                        ->deselectRecordsAfterCompletion(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    // Document types are centralized in App\Support\TaxistaDocumentTypes.

    private static function resolveDocumentStoragePath(TaxistaDocument $record): ?string
    {
        $path = trim((string)$record->file_path);

        if ($path === '') {
            return null;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, 'storage/')) {
            $path = Str::after($path, 'storage/');
        }

        if (Str::startsWith($path, 'public/')) {
            $path = Str::after($path, 'public/');
        }

        return $path;
    }

    private static function resolveDocumentPublicUrl(TaxistaDocument $record): ?string
    {
        $path = self::resolveDocumentStoragePath($record);

        if (!$path || !Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private static function resolveDocumentFilename(TaxistaDocument $record, string $path): string
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

    private static function applyDocumentSearch(Builder $query, string $search): Builder
    {
        $search = trim($search);

        if ($search === '') {
            return $query;
        }

        $likeSearch = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search) . '%';

        return $query->where(function (Builder $searchQuery) use ($likeSearch): void {
            $searchQuery
                ->where('title', 'like', $likeSearch)
                ->orWhere('document_type', 'like', $likeSearch)
                ->orWhere('status', 'like', $likeSearch)
                ->orWhere('meta->nif', 'like', $likeSearch)
                ->orWhere('meta->reference', 'like', $likeSearch)
                ->orWhereHas('taxista', function (Builder $taxistaQuery) use ($likeSearch): void {
                    $taxistaQuery
                        ->where('name', 'like', $likeSearch)
                        ->orWhere('nif', 'like', $likeSearch)
                        ->orWhere('email', 'like', $likeSearch);
                });
        });
    }
}
