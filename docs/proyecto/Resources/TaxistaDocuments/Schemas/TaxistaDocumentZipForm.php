<?php

namespace App\Filament\App\Resources\TaxistaDocuments\Schemas;

use App\Models\Taxista;
use App\Models\TaxiCentral\DocumentType;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Schema as Form;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Spatie\PdfToText\Pdf;
use SplFileInfo;
use ZipArchive;

class TaxistaDocumentZipForm
{
    public static function configure(): array
    {
        return [
            Section::make('Procesamiento ZIP de Documentos')
                ->description('Sube un archivo ZIP con PDFs para procesarlos automáticamente. El sistema detectará el NIF de cada PDF y lo asociará al taxista correspondiente.')
                ->columnSpanFull()
                ->schema([

                    FileUpload::make('zip_file')
                        ->label('Archivo ZIP')
                        ->helperText('Solo archivos ZIP con PDFs dentro. Máximo 5 MB.')
                        ->live(onBlur: true)
                        ->disk('public')
                        ->preserveFilenames()
                        ->directory('temp_zips')
                        ->acceptedFileTypes([
                            'application/zip',
                            '.zip',
                        ])
                        ->maxSize(5120)
                        ->required()
                        ->afterStateUpdated(function (mixed $state, Set $set): void {
                            if (! $state) {
                                return;
                            }

                            $originalName = $state->getClientOriginalName();
                            $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

                            if ($ext !== 'zip') {
                                Notification::make()
                                    ->title('Error')
                                    ->body('Solo se permiten archivos ZIP')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            // Procesar el ZIP y actualizar el reporte
                            self::processZipFile($state, $originalName, $set);
                        })
                        ->columnSpanFull(),

                    // Área para mostrar reporte
                    Section::make('Reporte de Procesamiento')
                        ->description('Los resultados del procesamiento aparecerán aquí.')
                        ->columnSpanFull()
                        ->schema([
                            Textarea::make('reporte')
                                ->label('Resultados')
                                ->rows(8)
                                ->readOnly()
                                ->default('Esperando procesamiento...')
                        ]),

                ])
                ->columns(1),

        ];
    }

    private static function processZipFile($state, $originalName, Set $set): void
    {
        $zip = new ZipArchive;
        $zipPath = $state->getRealPath();

        if ($zip->open($zipPath) === true) {
            $extractPath = storage_path('app/tmp_uploads/' . uniqid('zip_', true));
            if (! is_dir($extractPath)) {
                mkdir($extractPath, 0777, true);
            }
            $zip->extractTo($extractPath);
            $zip->close();

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath)
            );

            $totalPdfs = 0;
            $procesados = 0;
            $conNif = 0;
            $asociados = 0;
            $sinAsociar = 0;
            $detalles = [];

            foreach ($iterator as $file) {
                if (self::shouldIgnoreFile($file, $extractPath)) {
                    continue;
                }

                $totalPdfs++;
                $pdfFile = $file->getPathname();
                $pdfName = $file->getFilename();
                $pdfOriginalName = pathinfo($pdfName, PATHINFO_FILENAME);
                $relativePath = ltrim(str_replace($extractPath, '', $pdfFile), DIRECTORY_SEPARATOR);
                $pdfText = self::extractPdfText($pdfFile);
                $nif = self::extractNif($pdfOriginalName, $pdfText);

                // Buscar taxista por NIF
                $taxistaId = null;
                if ($nif) {
                    $conNif++;
                    $taxista = Taxista::where('nif', $nif)->first();
                    if ($taxista) {
                        $taxistaId = $taxista->id;
                        $asociados++;
                    } else {
                        $sinAsociar++;
                        $detalles[] = "❌ {$pdfName}: NIF {$nif} no encontrado";
                        continue;
                    }
                } else {
                    $sinAsociar++;
                    $detalles[] = "❌ {$pdfName}: NIF no detectado";
                    continue;
                }

                $taxModel = self::detectTaxModel($relativePath, $pdfOriginalName, $pdfText);
                $tipo = self::detectDocumentType($relativePath, $pdfOriginalName, $pdfText, $taxModel);

                // Detectar año y mes
                ['year' => $year, 'month' => $mes, 'period' => $period] = self::extractDateMetadata($pdfOriginalName, $pdfText);
                $title = self::buildTitle($pdfOriginalName, $taxModel, $period);

                // Guardar PDF
                $storagePath = "taxistas/documents/{$year}/{$nif}/{$tipo}/";
                Storage::disk('public')->putFileAs(
                    $storagePath,
                    new \Illuminate\Http\File($pdfFile),
                    $pdfName
                );

                // Crear registro del documento con el nombre del tipo de documento
                \App\Models\TaxistaDocument::create([
                    'taxista_user_id' => $taxistaId,
                    'uploaded_by_user_id' => auth()->id(),
                    'title' => $title,
                    'document_type' => $tipo,
                    'file_path' => $storagePath . $pdfName,
                    'status' => 'activo',
                    'is_favorite' => false,
                    'uploaded_at' => now(),
                    'notas' => 'Importado desde ZIP: ' . $originalName,
                    'meta' => [
                        'imported_from_zip' => $originalName,
                        'original_filename' => $pdfName,
                        'year' => $year,
                        'mes' => $mes,
                        'tipo' => $tipo,
                        'nif' => $nif,
                        'tax_model' => $taxModel,
                        'period' => $period,
                        'source_directory' => dirname($relativePath),
                    ],
                ]);

                $procesados++;
                $modelInfo = $taxModel ? " | modelo {$taxModel}" : '';
                $typeInfo = $tipo !== '' ? " | tipo {$tipo}" : '';
                $detalles[] = "✅ {$pdfName}: Asociado a taxista con NIF {$nif}{$typeInfo}{$modelInfo}";
            }

            // Limpiar directorio temporal
            \Illuminate\Support\Facades\File::deleteDirectory($extractPath);

            // Generar reporte
            $reporte = "📊 **REPORTE DE PROCESAMIENTO ZIP**\n";
            $reporte .= "📁 Archivo: {$originalName}\n\n";
            $reporte .= "📈 **ESTADÍSTICAS:**\n";
            $reporte .= "• Total PDFs encontrados: {$totalPdfs}\n";
            $reporte .= "• Con NIF detectado: {$conNif}\n";
            $reporte .= "• Asociados correctamente: {$asociados}\n";
            $reporte .= "• Sin asociar: {$sinAsociar}\n";
            $reporte .= "• Procesados con éxito: {$procesados}\n\n";

            if (!empty($detalles)) {
                $reporte .= "📋 **DETALLES:**\n";
                $reporte .= implode("\n", array_slice($detalles, 0, 10));
                if (count($detalles) > 10) {
                    $reporte .= "\n... y " . (count($detalles) - 10) . " más";
                }
            }

            // Actualizar el campo reporte en el formulario
            $set('reporte', $reporte);

            // Notificación con resultados
            Notification::make()
                ->title('ZIP Procesado')
                ->body("Se encontraron {$totalPdfs} PDFs, se asociaron {$asociados} documentos correctamente.")
                ->success()
                ->persistent()
                ->send();

        } else {
            // Actualizar el campo reporte con error
            $set('reporte', "❌ **ERROR DE PROCESAMIENTO**\n\nNo se pudo abrir el archivo ZIP: {$originalName}\n\nPor favor, verifique que el archivo no esté corrupto y tenga el formato correcto.");

            Notification::make()
                ->title('Error')
                ->body('No se pudo abrir el archivo ZIP')
                ->danger()
                ->send();
        }
    }

    private static function shouldIgnoreFile(SplFileInfo $file, string $extractPath): bool
    {
        if ($file->isDir()) {
            return true;
        }

        if (strtolower($file->getExtension()) !== 'pdf') {
            return true;
        }

        $filename = $file->getFilename();
        if (str_starts_with($filename, '.') || str_starts_with($filename, '._')) {
            return true;
        }

        $relativePath = ltrim(str_replace($extractPath, '', $file->getPathname()), DIRECTORY_SEPARATOR);
        $normalizedPath = str_replace('\\', '/', $relativePath);

        return str_contains($normalizedPath, '__MACOSX/');
    }

    private static function extractPdfText(string $pdfPath): string
    {
        try {
            return (string) Pdf::getText($pdfPath);
        } catch (\Throwable) {
            return '';
        }
    }

    private static function extractNif(string $filename, string $pdfText): ?string
    {
        $sources = [$filename, $pdfText];

        foreach ($sources as $source) {
            if (! preg_match_all('/\b(\d{8})([A-Za-z])\b/u', $source, $matches, PREG_SET_ORDER)) {
                continue;
            }

            foreach ($matches as $match) {
                $nif = self::validateNif($match[1], $match[2]);

                if ($nif !== null) {
                    return $nif;
                }
            }
        }

        return null;
    }

    private static function validateNif(string $number, string $letter): ?string
    {
        $letters = 'TRWAGMYFPDXBNJZSQVHLCKE';
        $expectedLetter = $letters[((int) $number) % 23] ?? null;
        $candidateLetter = mb_strtoupper($letter);

        if ($expectedLetter === null || $candidateLetter !== $expectedLetter) {
            return null;
        }

        return $number . $candidateLetter;
    }

    private static function detectDocumentType(string $relativePath, string $pdfOriginalName, string $pdfText, ?string $taxModel): string
    {
        if ($taxModel !== null) {
            return 'impuesto';
        }

        $haystack = self::normalizeText($relativePath . ' ' . $pdfOriginalName . ' ' . $pdfText);

        $databaseMappings = [
            'nomina' => 'nomina',
            'cuota' => 'cuotas',
            'agencia' => 'agencias',
            'repuesto' => 'repuestos',
            'seguro' => 'seguro',
            'impuesto' => 'impuesto',
            'certificado' => 'certificado',
        ];

        foreach ($databaseMappings as $term => $type) {
            if (str_contains($haystack, $term)) {
                return $type;
            }
        }

        $documentType = DocumentType::query()
            ->get(['name'])
            ->first(function (DocumentType $documentType) use ($haystack): bool {
                return str_contains($haystack, self::normalizeText($documentType->name));
            });

        if ($documentType) {
            $normalizedName = self::normalizeText($documentType->name);

            return match (true) {
                str_contains($normalizedName, 'nomina') => 'nomina',
                str_contains($normalizedName, 'cuota') => 'cuotas',
                str_contains($normalizedName, 'agencia') => 'agencias',
                str_contains($normalizedName, 'repuesto') => 'repuestos',
                str_contains($normalizedName, 'seguro') => 'seguro',
                str_contains($normalizedName, 'impuesto') => 'impuesto',
                str_contains($normalizedName, 'certificado') => 'certificado',
                default => 'otros',
            };
        }

        return 'otros';
    }

    private static function detectTaxModel(string $relativePath, string $pdfOriginalName, string $pdfText): ?string
    {
        $sources = [
            self::normalizeText($pdfOriginalName),
            self::normalizeText($relativePath),
            self::normalizeText($pdfText),
        ];

        if (preg_match('/(?:^|[^0-9])(036|037|039|100|111|115|130|131|180|184|190|200|216|303|347|349|390|400|421|425)(?=\d{6,})/u', self::normalizeText($pdfOriginalName), $matches)) {
            return $matches[1];
        }

        foreach ($sources as $source) {
            if (preg_match('/\bmod(?:elo)?\s*0?(\d{2,3})\b/u', $source, $matches)) {
                return str_pad($matches[1], 3, '0', STR_PAD_LEFT);
            }
        }

        foreach ($sources as $source) {
            if (preg_match('/\b(036|037|039|100|111|115|130|131|180|184|190|200|216|303|347|349|390|400|421|425)\b/u', $source, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    /**
     * @return array{year:string, month:string, period:?string}
     */
    private static function extractDateMetadata(string $pdfOriginalName, string $pdfText): array
    {
        $year = now()->format('Y');
        $month = now()->format('m');
        $period = null;
        $haystack = $pdfOriginalName . ' ' . $pdfText;

        if (preg_match('/\b([1-4])T(?:\s+\d{3})?[\s\-_\/]*(20\d{2})\b/u', $haystack, $quarterMatches)) {
            $period = $quarterMatches[1] . 'T ' . $quarterMatches[2];
            $year = $quarterMatches[2];
            $month = match ($quarterMatches[1]) {
                '1' => '03',
                '2' => '06',
                '3' => '09',
                '4' => '12',
            };

            return [
                'year' => (string) $year,
                'month' => (string) $month,
                'period' => $period,
            ];
        }

        if (preg_match('/\b(\d{2})[-\/](\d{2})[-\/](20\d{2})\b/u', $haystack, $fullDateMatches)) {
            return [
                'year' => $fullDateMatches[3],
                'month' => $fullDateMatches[2],
                'period' => null,
            ];
        }

        if (preg_match('/\b(20\d{2})(0[1-9]|1[0-2])(\d{2})\b/u', $haystack, $compactDateMatches)) {
            return [
                'year' => $compactDateMatches[1],
                'month' => $compactDateMatches[2],
                'period' => null,
            ];
        }

        if (preg_match('/\b(20\d{2})[-_\/](0[1-9]|1[0-2])\b/u', $haystack, $dateMatches)) {
            $year = $dateMatches[1];
            $month = $dateMatches[2];
        } elseif (preg_match('/\b(20\d{2})\b/u', $haystack, $yearMatches)) {
            $year = $yearMatches[1];
        }

        return [
            'year' => (string) $year,
            'month' => (string) $month,
            'period' => $period,
        ];
    }

    private static function buildTitle(string $pdfOriginalName, ?string $taxModel, ?string $period): string
    {
        $baseTitle = trim(str_replace(['-', '_'], ' ', $pdfOriginalName));
        $baseTitle = preg_replace('/\s+/u', ' ', $baseTitle) ?? $baseTitle;

        if ($taxModel === null) {
            return $baseTitle;
        }

        if (str_contains(self::normalizeText($baseTitle), 'modelo ' . self::normalizeText($taxModel))) {
            return $baseTitle;
        }

        $prefix = 'Modelo ' . $taxModel;

        if ($period !== null && ! str_contains($baseTitle, $period)) {
            return "{$prefix} - {$period}";
        }

        return "{$prefix} - {$baseTitle}";
    }

    private static function normalizeText(string $value): string
    {
        $ascii = Str::ascii($value);
        $ascii = mb_strtolower($ascii, 'UTF-8');

        return preg_replace('/\s+/u', ' ', $ascii) ?? $ascii;
    }
}
