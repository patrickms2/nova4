<?php

namespace App\Filament\App\Resources\Taxistas\Pages;

use App\Filament\App\Resources\TaxistaDocuments\Schemas\TaxistaDocumentForm;
use App\Filament\App\Resources\TaxistaDocuments\Tables\TaxistaDocumentsTable;
use App\Filament\App\Resources\Taxis\Schemas\TaxiForm;
use App\Filament\App\Resources\Taxistas\TaxistaResource;
use App\Models\TaxistaDocument as Document;
use App\Support\TaxistaDocumentTypes;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use setasign\Fpdi\Fpdi;
use Spatie\PdfToText\Pdf;

$kanbanPath = base_path('/app/Filament/App/Resources/Taxistas/Pages/ManageDocumentosKanbanTaxista.php');
if (file_exists($kanbanPath)) {
    require_once $kanbanPath;
}

class ManageDocumentosTaxista extends ManageRelatedRecords
{
    protected static string $resource = TaxistaResource::class;

    protected static string $relationship = 'documents';

    protected static ?string $navigationLabel = 'Documentos';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;
    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        $record = Livewire::current()->getRecord();

        return (string)$record->documents()->count();
    }


    protected function procesarCuotasMultipagina(string $pdfPath): int
    {
        $fpdi = new Fpdi();

        $pageCount = $fpdi->setSourceFile($pdfPath);
        $creados = 0;

        for ($page = 1; $page <= $pageCount; $page++) {

            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($pdfPath);
            $tplIdx = $pdf->importPage($page);
            $pdf->useTemplate($tplIdx);

            $tmpDir = storage_path('app/tmp_cuotas');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $tmpFile = $tmpDir . '/cuota_pag_' . $page . '_' . uniqid() . '.pdf';
            $pdf->Output($tmpFile, 'F');

            // Extraer texto
            try {
                $texto = Pdf::getText($tmpFile);
            } catch (\Throwable $e) {
                continue;
            }

            /**
             * 1) DETECTAR NIF
             */
            $nif = null;
            if (preg_match('/\b(\d{8})([A-Za-z])\b/', $texto, $m)) {
                $nif = strtoupper($m[1] . $m[2]);
            }

            if (!$nif) {
                // No se puede clasificar sin NIF → saltar página
                continue;
            }
            // === DETECTAR CÓDIGO INTERNO ===
            preg_match('/S\.COOP\s+(\d{4,6})/i', $texto, $codigoMatch);
            $codigoInterno = $codigoMatch[1] ?? null;

// === DETECTAR NOMBRE DEL CLIENTE ===
            preg_match('/S\.COOP\s+\d{4,6}\s+([A-ZÁÉÍÓÚÑ ]{5,})/iu', $texto, $nombreMatch);
            $nombreCliente = isset($nombreMatch[1])
                ? trim($nombreMatch[1])
                : 'Generado cuotas ' . $nif;

// Puedes guardarlo:
            $extra = [
                'cliente_codigo' => $codigoInterno,
                'cliente_nombre' => $nombreCliente,
            ];


            /**
             * 2) DETECTAR FECHA (dd-mm-yy)
             */
            $year = (int)date('Y');
            $mes = date('m');

            if (preg_match('/Fecha:\s*(\d{2})-(\d{2})-(\d{2})/', $texto, $mFecha)) {
                $dia = $mFecha[1];
                $mes = $mFecha[2];
                $yy = $mFecha[3];
                $year = (int)('20' . $yy);
            }

            $mesNorm = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);

            /**
             * 3) CLASIFICACIÓN AUTOMÁTICA POR CONCEPTO
             */
            $textoUpper = strtoupper($texto);

            if (str_contains($textoUpper, 'SEGURO COLECTIVO') ||
                str_contains($textoUpper, 'PÓLIZA') ||
                str_contains($textoUpper, 'POLIZA') ||
                str_contains($textoUpper, 'PRIMA')) {

                $tipodoc = 'seguro';

            } elseif (str_contains($textoUpper, 'TARIFA ESPECIAL')) {

                $tipodoc = 'tarifa-especial';

            } elseif (str_contains($textoUpper, 'CONSORCIO') ||
                str_contains($textoUpper, 'FUNDACIÓN') ||
                str_contains($textoUpper, 'FUNDACION')) {

                $tipodoc = 'consorcio';

            } elseif (str_contains($textoUpper, 'CUOTA')) {

                $tipodoc = 'cuota';

            } else {
                // por defecto
                $tipodoc = 'cuota';
            }


            /**
             * 4) SI EL USUARIO NO EXISTE, CREARLO
             */
            $usuario = Usuario::where('cif', $nif)->first();

            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => $nombreCliente,
                    'cif' => $nif,
                    'tipo_id' => 4,
                ]);
            }

            $usuarioId = $usuario->id;
            $departamentoId = $usuario->tipo_id;

            $tipoRow = DocumentType::query()->select(['id'])->where('name', 'like', '%' . $tipodoc . '%')->first();
            $tipo_id = $tipoRow?->id;

            /**
             * 5) NOMBRE FINAL DEL ARCHIVO
             */
            $fileName = "{$tipodoc}__{$nif}__{$year}{$mesNorm}.pdf";

            /**
             * 6) GUARDAR EN STORAGE
             */
            $storagePath = "{$year}/{$nif}/{$tipodoc}/";

            Storage::disk('documentos')->putFileAs(
                $storagePath,
                new \Illuminate\Http\File($tmpFile),
                $fileName
            );
            /**
             * 7) GUARDAR EN BD
             */
            Document::create([
                'attachment_file_names' => $fileName,
                'attachments' => $storagePath,
                'nif' => $nif,
                'departamento_id' => $departamentoId,
                'usuario_id' => $usuarioId,
                'tipodoc' => $tipodoc,
                'document_type_id' => $tipo_id,
                'year' => $year,
                'mes' => $mesNorm,
                'notas' => json_encode($extra),
            ]);

            $creados++;
        }

        return $creados;
    }

    protected function procesarAgenciasMultipagina(string $pdfPath): int
    {
        $fpdi = new Fpdi();

        $pageCount = $fpdi->setSourceFile($pdfPath);
        $creados = 0;

        for ($page = 1; $page <= $pageCount; $page++) {

            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($pdfPath);
            $tplIdx = $pdf->importPage($page);
            $pdf->useTemplate($tplIdx);

            $tmpDir = storage_path('app/tmp_agencias');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $tmpFile = $tmpDir . '/agencias_pag_' . $page . '_' . uniqid() . '.pdf';
            $pdf->Output($tmpFile, 'F');

            // Extraer texto
            try {
                $texto = Pdf::getText($tmpFile);
            } catch (\Throwable $e) {
                continue;
            }

            if (preg_match('/FACTURA :\s+([A-Z0-9]{1,4}\s)/i', $texto, $m)) {
                // Normaliza espacios: "A/ 251024" -> "A/251024"
                $numeroFactura = preg_replace('/\s+/', '', $m[1]);
                // $numeroFactura contiene "A/251024"
            } else {
                $numeroFactura = '';
            }

            if ($numeroFactura != '') {
                $facturas = TaxistaDocument::where('referencia', $numeroFactura)->count();
                if ($facturas > 0) {
                    continue;
                }
            }

            /**
             * 1) DETECTAR NIF
             */
            $nif = null;
            if (preg_match('/\b(\d{8})([A-Za-z])\b/', $texto, $m)) {
                $nif = strtoupper($m[1] . $m[2]);
            }

            if (!$nif) {
                // No se puede clasificar sin NIF → saltar página
                continue;
            }
            // === DETECTAR CÓDIGO INTERNO ===
            preg_match('/S\.COOP\s+(\d{4,6})/i', $texto, $codigoMatch);
            $codigoInterno = $codigoMatch[1] ?? null;

// === DETECTAR NOMBRE DEL CLIENTE ===
            preg_match('/S\.COOP\s+\d{4,6}\s+([A-ZÁÉÍÓÚÑ ]{5,})/iu', $texto, $nombreMatch);
            $nombreCliente = isset($nombreMatch[1])
                ? trim($nombreMatch[1])
                : 'Generado cuotas ' . $nif;

// Puedes guardarlo:
            $extra = [
                'cliente_codigo' => $codigoInterno,
                'cliente_nombre' => $nombreCliente,
            ];


            /**
             * 2) DETECTAR FECHA (dd-mm-yy)
             */
            $year = (int)date('Y');
            $mes = date('m');

            if (preg_match('/FECHA:\s*(\d{2})-(\d{2})-(\d{2})/', $texto, $mFecha)) {
                $dia = $mFecha[1];
                $mes = $mFecha[2];
                $yy = $mFecha[3];
                $year = (int)('20' . $yy);
            }

            $mesNorm = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);

            /**
             * 3) CLASIFICACIÓN AUTOMÁTICA POR CONCEPTO
             */
            $textoUpper = strtoupper($texto);

            if (str_contains($textoUpper, 'FACTURA') ||
                str_contains($textoUpper, 'SERVICIOS')) {

                $tipodoc = 'agencias';

            } else {
                // por defecto
                $tipodoc = 'agencias';
            }


            /**
             * 4) SI EL USUARIO NO EXISTE, CREARLO
             */
            $usuario = Usuario::where('cif', $nif)->first();

            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => $nombreCliente,
                    'cif' => $nif,
                    'tipo_id' => 4,
                ]);
            }

            $usuarioId = $usuario->id;
            $departamentoId = $usuario->tipo_id;

            $tipoRow = DocumentType::query()->select(['id'])->where('name', 'like', '%' . $tipodoc . '%')->first();
            $tipo_id = $tipoRow?->id;

            /**
             * 5) NOMBRE FINAL DEL ARCHIVO
             */
            $fileName = "{$tipodoc}__{$nif}__{$year}{$mesNorm}.pdf";

            /**
             * 6) GUARDAR EN STORAGE
             */
            $storagePath = "{$year}/{$nif}/{$tipodoc}/";

            Storage::disk('documentos')->putFileAs(
                $storagePath,
                new \Illuminate\Http\File($tmpFile),
                $fileName
            );

            /**
             * 7) GUARDAR EN BD
             */
            Document::create([
                'attachment_file_names' => $fileName,
                'attachments' => $storagePath,
                'nif' => $nif,
                'departamento_id' => $departamentoId,
                'usuario_id' => $usuarioId,
                'tipodoc' => $tipodoc,
                'referencia' => $numeroFactura,
                'document_type_id' => $tipo_id,
                'year' => $year,
                'mes' => $mesNorm,
                'notas' => json_encode($extra),
            ]);

            $creados++;
        }

        return $creados;
    }

    function buscarDocumentoAntesDeEtiqueta(string $texto, string $etiqueta): ?string
    {
        // Convertir a array de líneas
        $lineas = preg_split('/\R/', $texto);

        foreach ($lineas as $i => $linea) {
            if (stripos($linea, $etiqueta) !== false) {

                // Buscar en las 2 líneas anteriores
                for ($j = 1; $j <= 2; $j++) {
                    $lineaPrevia = $lineas[$i - $j] ?? '';

                    if (preg_match('/\b([0-9]{8}[A-Z])\b/i', $lineaPrevia, $m)) {
                        return strtoupper($m[1]);
                    }
                }
            }
        }

        return null;
    }

    protected function procesarNominasMultipagina(string $pdfPath): int
    {
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $creados = 0;

        for ($page = 1; $page <= $pageCount; $page++) {

            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($pdfPath);
            $tplIdx = $pdf->importPage($page);
            $pdf->useTemplate($tplIdx);

            $tmpDir = storage_path('app/tmp_nominas');
            if (!is_dir($tmpDir)) mkdir($tmpDir, 0777, true);

            $tmpFile = $tmpDir . '/nomina_pag_' . $page . '_' . uniqid() . '.pdf';
            $pdf->Output($tmpFile, 'F');

            // EXTRAER TEXTO
            try {
                $texto = Pdf::getText($tmpFile);
            } catch (\Throwable $e) {
                continue;
            }
            $cifAntes = $this->buscarDocumentoAntesDeEtiqueta($texto, 'CIF');
            $nifAntes = $this->buscarDocumentoAntesDeEtiqueta($texto, 'NIF');
            if (!$nifAntes) {
                preg_match('/NIF[:\s]*([0-9]{8}[A-Z])|NIF:\s*\n\s*([0-9]{8}[A-Z])/i', $texto, $n);
                $nifTrabajador = strtoupper($n[1] ?? $n[2] ?? '');
            } else {
                $nifTrabajador = $nifAntes;
            }

            if (!$cifAntes) {
                preg_match('/CIF[:\s]*([0-9]{8}[A-Z])|CIF:\s*\n\s*([0-9]{8}[A-Z])/i', $texto, $c);
                $cifEmpresa = strtoupper($c[1] ?? $c[2] ?? '');
            } else {
                $cifEmpresa = $cifAntes;
            }
            // === DETECTAR NIF ===
// === EMPRESA ===
            preg_match('/Empresa:\s*([A-ZÁÉÍÓÚÑ ]{5,})/iu', $texto, $matchEmp);
            $nombreEmpresa = trim($matchEmp[1] ?? '');


// === TRABAJADOR ===
            preg_match('/Trabajador:\s*([A-ZÁÉÍÓÚÑ ]{5,})/iu', $texto, $matchTrab);
            $nombreTrabajador = trim($matchTrab[1] ?? '');

            $extras = [
                'empresa_nombre' => $nombreEmpresa,
                'empresa_cif' => $cifEmpresa,
                'trabajador_nombre' => $nombreTrabajador,
                'trabajador_nif' => $nifTrabajador,
            ];

            $notas = json_encode($extras, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            //if (!$nif) continue;
            $nif = null;
            if (preg_match('/\b(\d{8})([A-Za-z])\b/', $texto, $m)) {
                $nif = strtoupper($m[1] . $m[2]);
            }

            // === DETECTAR NOMBRE DEL TRABAJADOR ===
            preg_match('/([\p{L}\s]+)\s*\n\s*' . $nif . '/u', $texto, $nameMatch);
            $nombre = trim($nameMatch[1] ?? 'Trabajador ' . $nif);

            // === DETECTAR AÑO Y MES ===
            preg_match('/Periodo de liquidación.*?\b([A-Za-z]+)\b.*?(\d{4})/si', $texto, $periodo);
            $mesTexto = strtolower($periodo[1] ?? '');
            $year = $periodo[2] ?? date('Y');

            $meses = [
                'enero' => '01', 'febrero' => '02', 'marzo' => '03', 'abril' => '04', 'mayo' => '05',
                'junio' => '06', 'julio' => '07', 'agosto' => '08', 'septiembre' => '09',
                'octubre' => '10', 'noviembre' => '11', 'diciembre' => '12'
            ];
            $mes = $meses[$mesTexto] ?? date('m');

            // === TIPO DOC ===
            $tipodoc = 'nomina';
            $tipoRow = TaxiDocumentType::query()->where('name', 'like', '%nomina%')->first();
            $tipo_id = $tipoRow?->id ?? 1;

            // === CREAR USUARIO SI NO EXISTE ===
            $usuario = Usuario::where('cif', $nif)->first();

            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => $nombreEmpresa,
                    'cif' => $cifEmpresa,
                    'tipo_id' => 4,
                ]);
            }

            $conductor = Usuario::where('cif', $nifTrabajador)->first();

            if (!$conductor) {
                $conductor = Usuario::create([
                    'nombre' => $nombreTrabajador,
                    'cif' => $nifTrabajador,
                    'taxista_id' => $usuario->id,
                    'municipio_id' => $usuario->municipio_id,
                    'tipo_id' => 7,
                ]);
            }
            $usuarioId = $usuario->id;
            $conductorId = $conductor->id;
            $departamentoId = $usuario->tipo_id;

            // === GUARDAR PDF ===
            $fileName = "nomina__{$nif}__{$year}{$mes}.pdf";
            $storagePath = "{$year}/{$nif}/{$tipodoc}/";

            Storage::disk('documentos')->putFileAs(
                $storagePath,
                new \Illuminate\Http\File($tmpFile),
                $fileName
            );

            // === BD ===
            Document::create([
                'attachment_file_names' => $fileName,
                'attachments' => $storagePath,
                'nif' => $nif,
                'departamento_id' => $departamentoId,
                'usuario_id' => $usuarioId,
                'conductor_id' => $conductorId,
                'document_type_id' => $tipo_id,
                'tipodoc' => $tipodoc,
                'year' => $year,
                'mes' => $mes,
                'notas' => $notas,
            ]);

            $creados++;
        }

        return $creados;
    }

    protected function procesarSegurosMultipagina(string $pdfPath): int
    {
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $creados = 0;

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($pdfPath);
            $tplIdx = $pdf->importPage($page);
            $pdf->useTemplate($tplIdx);

            $tmpDir = storage_path('app/tmp_seguros');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $tmpFile = $tmpDir . '/seguro_pag_' . $page . '_' . uniqid() . '.pdf';
            $pdf->Output($tmpFile, 'F');

            try {
                $texto = Pdf::getText($tmpFile);
            } catch (\Throwable $e) {
                continue;
            }

            if (!preg_match('/\b(\d{8})([A-Za-z])\b/', $texto, $m)) {
                continue;
            }

            $nif = strtoupper($m[1] . $m[2]);

            if (preg_match('/(\d{2})-(\d{2})-(\d{2})/', $texto, $mFecha)) {
                $mes = $mFecha[2];
                $year = (int)('20' . $mFecha[3]);
            } else {
                $year = (int)date('Y');
                $mes = date('m');
            }

            $mesNorm = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
            $tipodoc = 'seguro';


            if (preg_match('/FACTURA\s+([A-Z0-9]{1,4}\s*\/\s*\d{4,8})/i', $texto, $m)) {
                // Normaliza espacios: "A/ 251024" -> "A/251024"
                $numeroFactura = preg_replace('/\s+/', '', $m[1]);
                // $numeroFactura contiene "A/251024"
            } else {
                $numeroFactura = '';
            }


            if ($numeroFactura != '') {
                $facturas = Document::where('referencia', $numeroFactura)->count();
                if ($facturas > 0) {
                    continue;
                }
            }
            $tipoRow = DocumentType::query()->select(['id'])->where('name', 'like', '%' . $tipodoc . '%')->first();
            $tipo_id = $tipoRow?->id;

            $usuario = Usuario::where('cif', $nif)->first();
            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => 'Generado ' . $nif,
                    'cif' => $nif,
                    'tipo_id' => 4,
                ]);
            }

            $usuarioId = $usuario->id;
            $departamentoId = $usuario->tipo_id;

            $fileName = "seguro__{$nif}__{$year}{$mesNorm}.pdf";
            $storagePath = "{$year}/{$nif}/{$tipodoc}/";

            Storage::disk('documentos')->putFileAs(
                $storagePath,
                new \Illuminate\Http\File($tmpFile),
                $fileName
            );

            Document::create([
                'attachment_file_names' => $fileName,
                'attachments' => $storagePath,
                'nif' => $nif,
                'departamento_id' => $departamentoId,
                'usuario_id' => $usuarioId,
                'document_type_id' => $tipo_id,
                'referencia' => $numeroFactura,
                'tipodoc' => $tipodoc,
                'year' => $year,
                'mes' => $mesNorm,
            ]);

            $creados++;
        }

        return $creados;
    }

    protected function procesarRepuestosMultipagina(string $pdfPath): int
    {
        $fpdi = new Fpdi();
        $pageCount = $fpdi->setSourceFile($pdfPath);
        $creados = 0;

        for ($page = 1; $page <= $pageCount; $page++) {
            $pdf = new Fpdi();
            $pdf->AddPage();
            $pdf->setSourceFile($pdfPath);
            $tplIdx = $pdf->importPage($page);
            $pdf->useTemplate($tplIdx);

            $tmpDir = storage_path('app/tmp_repuestos');
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0777, true);
            }

            $tmpFile = $tmpDir . '/repuesto_pag_' . $page . '_' . uniqid() . '.pdf';
            $pdf->Output($tmpFile, 'F');

            try {
                $texto = Pdf::getText($tmpFile);
            } catch (\Throwable $e) {
                continue;
            }

            if (!preg_match('/\b(\d{8})([A-Za-z])\b/', $texto, $m)) {
                continue;
            }

            $nif = strtoupper($m[1] . $m[2]);

            if (preg_match('/(\d{2})-(\d{2})-(\d{2})/', $texto, $mFecha)) {
                $mes = $mFecha[2];
                $year = (int)('20' . $mFecha[3]);
            } else {
                $year = (int)date('Y');
                $mes = date('m');
            }

            $mesNorm = str_pad((string)$mes, 2, '0', STR_PAD_LEFT);
            $tipodoc = 'repuesto';

            if (preg_match('/FACTURA\s+([A-Z0-9]{1,4}\s*\/\s*\d{4,8})/i', $texto, $m)) {
                // Normaliza espacios: "A/ 251024" -> "A/251024"
                $numeroFactura = preg_replace('/\s+/', '', $m[1]);
                // $numeroFactura contiene "A/251024"
            } else {
                $numeroFactura = '';
            }


            if ($numeroFactura != '') {
                $facturas = Document::where('referencia', $numeroFactura)->count();
                if ($facturas > 0) {
                    continue;
                }
            }
            $usuario = Usuario::where('cif', $nif)->first();
            if (!$usuario) {
                $usuario = Usuario::create([
                    'nombre' => 'Generado Automático',
                    'cif' => $nif,
                    'tipo_id' => 4,
                ]);
            }

            $usuarioId = $usuario->id;
            $departamentoId = $usuario->tipo_id;

            $tipoRow = DocumentType::query()->select(['id'])->where('name', 'like', '%' . $tipodoc . '%')->first();
            $tipo_id = $tipoRow?->id;

            $fileName = "repuesto__{$nif}__{$year}{$mesNorm}.pdf";
            $storagePath = "{$year}/{$nif}/{$tipodoc}/";

            Storage::disk('documentos')->putFileAs(
                $storagePath,
                new \Illuminate\Http\File($tmpFile),
                $fileName
            );

            Document::create([
                'attachment_file_names' => $fileName,
                'attachments' => $storagePath,
                'nif' => $nif,
                'departamento_id' => $departamentoId,
                'usuario_id' => $usuarioId,
                'document_type_id' => $tipo_id,
                'referencia' => $numeroFactura,
                'tipodoc' => $tipodoc,
                'year' => $year,
                'mes' => $mesNorm,
            ]);

            $creados++;
        }

        return $creados;
    }

    public function makeDocumentoTipo($data): void
    {
        try {
            $documento = new Document();
            $documento->document_type_id = $data['document_type_id'] ?? null;
            $documento->file_name = $data['file_name'];
            $documento->attachment_file_names = $data['attachment_file_names'] ?? $data['file_name'] ?? null;
            $documento->attachments = $data['attachments'] ?? null;

            $documento->tipo = $data['tipodoc'];
            $documento->year = $data['year'];
            $documento->mes = $data['mes'];
            $documento->nif = $data['nif'];
            $documento->usuario_id = $data['usuario_id'];
            $documento->save();


            Notification::make()
                ->title('Saved successfully')
                ->success()
                ->send();
        } catch (\Exception $e) {
            Notification::make()
                ->title($e->getMessage())
                ->danger()
                ->send();
        }

    }

    public function getCamposDocumentos($tipo = 'documento', $usuarioId = null): array
    {
        return [
            /*Select::make('tipo_id')
                ->label('tipodoc')
                ->options(function () {
                    return TipoUsuario::query()
                        ->where('estado', '=', 1)
                        ->pluck('nombre', 'id');
                })
                ->required()
                ->default(3)
                ->createOptionUsing(function (string $newValue) {
                    // Aquí creamos un nuevo registro en la tabla TipoUsuario
                    $tipo = TipoUsuario::create(['nombre' => $newValue, 'estado' => 1]);

                    return $tipo->id; // Retornamos el id del nuevo registro para que sea seleccionado automáticamente
                }),*/
            Select::make('usuario_id')
                ->label('Usuario')
                ->default(function () use ($usuarioId) {
                    return auth()->id();
                })
                ->options(function () {
                    return Usuario::query()
                        //->where('tipo_id', '<>', 2) // Filtra solo los usuarios correspondientes
                        ->pluck('nombre', 'id')
                        ->toArray();
                })
                ->preload()
                ->searchable()
                ->columnSpan('md'),
            Select::make('departamento_id')
                ->label('Departamento')
                ->options(fn() => Departamento::pluck('nombre', 'id')->toArray())
                ->searchable()
                ->nullable()
                ->preload()
                ->columnSpan('md'),
            TextInput::make('attachment_file_names')
                ->label('Nombre fichero')
                ->maxLength(255)
                ->default(null),
            FileUpload::make('attachments')
                ->acceptedFileTypes(['application/pdf', 'application/zip'])
                ->imagePreviewHeight('250')
                ->disk('documentos')
                ->reactive()
                ->live(onBlur: true)
                ->preserveFilenames(true)
                ->directory('documentos')
                ->storeFileNamesIn("attachment_file_names")
                ->afterStateUpdated(function ($state, Set $set, Get $get) {
                    if ($state) {
                        $originalFileName = pathinfo($state, PATHINFO_FILENAME);
                        $set('file_name', $originalFileName);
                    }
                    //$set('fileRealPath', $state->getRealPath());
                    if ($state) {
                        // Mantén el nombre original del archivo en 'file_name'
                        $originalFileName = pathinfo($state, PATHINFO_FILENAME);

                        $parts = explode('_', $originalFileName);


                        // $set('file_name', $originalFileName);
                        // Define el tipo según la extensión
                        $set('fileRealPath', $state->getRealPath());
                        $text = (new Pdf())
                            ->setPdf($state->getRealPath())
                            ->text();

                        // $set('file_name', $text);

                        preg_match('/Modelo\s+(\d+)/i', $text, $modeloMatch);
                        $modelo = $modeloMatch[1] ?? 'NO ENCONTRADO';
                        $set('modelo', $modelo);

                        // Lógica específica según el modelo detectado
                        switch ($modelo) {
                            case '421': // AUTOLIQUIDACIÓN: RÉGIMEN SIMPLIFICADO
                                preg_match('/Periodo de liquidaciónEJERCICIO.*?PERÍODO\s+([0-9A-Z]+)/i', $text, $periodoMatch);
                                $periodo = $periodoMatch[1] ?? 'NO ENCONTRADO';
                                $set('periodo', $periodo);

                                preg_match('/Nº DE JUSTIFICANTE\s+(\d+)/i', $text, $justificanteMatch);
                                $justificante = $justificanteMatch[1] ?? 'NO ENCONTRADO';
                                $set('justificante', $justificante);

                                preg_match('/N.I.F\.\s+([A-Z0-9]+)/i', $text, $nifMatch);
                                $nif = $nifMatch[1] ?? 'NO ENCONTRADO';
                                $set('nif', $nif);

                                preg_match('/Razón social\s+([A-ZÁÉÍÓÚÑ ]+)/iu', $text, $razonSocialMatch);
                                $razonSocial = $razonSocialMatch[1] ?? 'NO ENCONTRADO';
                                $set('razonSocial', $razonSocial);
                                break;

                            case '131': // MODELO 131
                                preg_match('/Presentación realizada el\s+([0-9\-:\s]+)/i', $text, $fechaPresentacionMatch);
                                $fechaPresentacion = $fechaPresentacionMatch[1] ?? 'NO ENCONTRADO';
                                $set('fecha_presentacion', $fechaPresentacion);

                                preg_match('/NIF Presentador:([A-Z0-9]+)/i', $text, $nifPresentadorMatch);
                                $nifPresentador = $nifPresentadorMatch[1] ?? 'NO ENCONTRADO';
                                $set('nif_presentador', $nifPresentador);

                                preg_match('/Apellidos y Nombre \/ Razón social:([A-ZÁÉÍÓÚÑ0-9\s]+)/iu', $text, $presentadorMatch);
                                $presentador = $presentadorMatch[1] ?? 'NO ENCONTRADO';
                                $set('presentador', $presentador);

                                preg_match('/Número justificante:\s+(\d+)/i', $text, $numJustificanteMatch);
                                $numJustificante = $numJustificanteMatch[1] ?? 'NO ENCONTRADO';
                                $set('num_justificante', $numJustificante);
                                break;

                            default: // Modelo desconocido o genérico
                                preg_match('/N.I.F\.\s+([A-Z0-9]+)/i', $text, $nifMatch);
                                $nif = $nifMatch[1] ?? 'NO ENCONTRADO';
                                $set('nif', $nif);

                                preg_match('/Razón social\s+([A-ZÁÉÍÓÚÑ ]+)/iu', $text, $nombreMatch);
                                $nombre = $nombreMatch[1] ?? 'NO ENCONTRADO';
                                $set('nombre', $nombre);

                                preg_match('/Fecha y hora de Presentación\s*:\s*([0-9\/\:\s]+)/i', $text, $fechaMatch);
                                $fecha = $fechaMatch[1] ?? 'NO ENCONTRADO';
                                $set('fecha', $fecha);
                                break;
                        }
                        $fileExtension = pathinfo($state, PATHINFO_EXTENSION);
                        $fileType = match (strtolower($fileExtension)) {
                            'pdf' => 'PDF',
                            'zip' => 'ZIP',
                            default => 'Desconocido',
                        };
                        $set('tipodoc', $fileType);
                    }
                }),
            Hidden::make('tipodoc')
                ->label('tipodoc')
                ->maxLength(155)
                ->default(null),
            Hidden::make('nif')
                ->label('NIF')
                ->maxLength(155)
                ->default(null),
            Hidden::make('year')
                ->label('Año')
                ->maxLength(155)
                ->default(null),
            Hidden::make('mes')
                ->label('Mes')
                ->maxLength(155)
                ->default(null),

            Select::make('document_type_id')
                ->label("Tipo")
                ->options(DocumentType::query()->where('is_active', 1)->orderBy('name')->pluck('name', 'id')->toArray())
                ->searchable()
                ->required(),

        ];
    }

    public function getHeading(): string|Htmlable|null
    {
        return $this->getRecord()->name;
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Documentación del taxista.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Añadir documento')
                ->hiddenLabel(true)
                ->color('danger')
                ->Button()
                ->icon('heroicon-s-document-plus')
                ->fillForm(fn(): array => [
                    'taxista_user_id' => $this->getRecord()->id,
                ])
                ->mutateFormDataUsing(function (array $data): array {
                    $data['taxista_user_id'] = $this->getRecord()->id;
                    $data['title'] = $data['title'] ?? ($data['file_name'] ?? ('Documento de ' . $this->getRecord()->nombre));

                    return $data;
                }),

            Action::make('Kanban')
                ->hiddenLabel(true)
                ->icon('heroicon-o-squares-2x2'),
            //->url(fn(Taxista $record): string => TaxistaResource::getUrl('documentos.kanban', ['record' => $record])),
            /*ActionGroup::make([


                Action::make('agencias_multipagina')
                    ->label('Agencias')
                    ->color('warning')
                    ->hiddenLabel(true)
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Subir Agencias')
                    ->modalCancelActionLabel('Cerrar')
                    ->icon('heroicon-s-squares-plus')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero PDF de Agencias (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarAgenciasMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total documentos");

                                    Notification::make()
                                        ->title('Agencias procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();


                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),


                Action::make('cuotas_multipagina')
                    ->label('Cuotas')
                    ->hiddenLabel(true)
                    ->color('warning')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Subir Cuotas')
                    ->modalCancelActionLabel('Cerrar')
                    ->icon('heroicon-s-squares-plus')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero PDF de cuotas (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarCuotasMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total cuotas");

                                    Notification::make()
                                        ->title('Cuotas procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();


                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),


                Action::make('nominas_multipagina')
                    ->label('Nóminas')
                    ->hiddenLabel(true)
                    ->color('success')
                    ->icon('heroicon-s-document-text')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Nóminas')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Nóminas (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->imagePreviewHeight('250')
                            ->reactive()
                            ->previewable(false)
                            ->storeFileNamesIn('attachment_file_names')
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarNominasMultipagina($pdfFile);

                                    $set('notas', "Se han procesado $total nóminas");

                                    Notification::make()
                                        ->title('Nóminas procesadas')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),

                Action::make('seguros_multipagina')
                    ->label('Seguros')
                    ->color('info')
                    ->hiddenLabel(true)
                    ->visible(false)
                    ->icon('heroicon-o-shield-check')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Seguros')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Seguros (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->previewable(false)
                            ->reactive()
                            ->storeFileNamesIn('attachment_file_names')
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarSegurosMultipagina($pdfFile);

                                    $set('notas', "Se han procesado $total seguros");

                                    Notification::make()
                                        ->title('Seguros procesados')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            }),

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),

                Action::make('repuestos_multipagina')
                    ->label('Repuestos')
                    ->color('primary')
                    ->hiddenLabel(true)
                    ->icon('heroicon-o-wrench')
                    ->modalWidth(Width::ExtraLarge)
                    ->modalSubmitActionLabel('Procesar Fact. Repuestos')
                    ->modalCancelActionLabel('Cerrar')
                    ->modalSubmitAction(false)
                    ->form(fn(Form $form) => [
                        FileUpload::make('attachments')
                            ->label('Fichero de Repuestos (multipágina)')
                            ->preserveFilenames()
                            ->disk('documentos')
                            ->acceptedFileTypes(['application/pdf'])
                            ->previewable(false)
                            ->reactive()
                            ->storeFileNamesIn('attachment_file_names')
                            ->afterStateUpdated(function ($state, $record, Set $set, Get $get) {

                                if ((gettype($state) == 'string') || ($state == null))
                                    return;

                                $originalName = $state->getClientOriginalName();
                                $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
                                $originalName = pathinfo($originalName, PATHINFO_FILENAME);
                                $file = pathinfo($originalName, PATHINFO_FILENAME);
                                $path = $state->getRealPath();

                                if ($ext === 'pdf') {

                                    $pdfFile = $state->getPathname();
                                    $pdfName = $state->getFilename();

                                    $total = $this->procesarRepuestosMultipagina($pdfFile);
                                    $set('notas', "Se han procesado $total fact. repuestos");

                                    Notification::make()
                                        ->title('Fact. Repuestos procesados')
                                        ->body("$total PDFs generados")
                                        ->success()
                                        ->send();

                                    return;

                                }

                                return;

                            })
                        ,

                        Textarea::make('notas')
                            ->label('Notas / incidencias')
                            ->helperText('Problemas detectados, comentarios internos...')
                            ->default(function (Set $set, Get $get) {
                                return $get('notas');
                            })
                            ->live(onBlur: true)
                            ->reactive()
                            ->rows(3),
                    ]),
            ]),*/
        ];

    }

    public function form(Schema $schema): Schema
    {
        return TaxistaDocumentForm::configure($schema);
    }

    public function table(Table $table): Table
    {
        return TaxistaDocumentsTable::configure($table);
    }

    public function getTabs(): array
    {
        $tabs = [
            'all' => Tab::make()
                ->label('Todos')
                ->badge(fn(): int => $this->getRecord()->documents()->count()),
        ];

        foreach (TaxistaDocumentTypes::options() as $type => $label) {
            $tabs[$type] = Tab::make()
                ->label($label)
                ->badge(fn(): int => $this->getRecord()->documents()
                    ->where('document_type', $type)
                    ->count())
                ->modifyQueryUsing(fn(Builder $query): Builder => $query->where('document_type', $type));
        }

        return $tabs;
    }

    private function resolveDocumentStoragePath(Document $record): ?string
    {
        $attachment = $record->file_path;
        $fileName = $record->title;

        if (!filled($attachment)) {
            return filled($fileName) ? ltrim((string)$fileName, '/') : null;
        }

        $attachment = trim((string)$attachment);

        if (Str::startsWith($attachment, ['http://', 'https://'])) {
            $path = parse_url($attachment, PHP_URL_PATH);
            if (is_string($path) && filled($path)) {
                $attachment = $path;
            }
        }

        $attachment = ltrim($attachment, '/');

        if (Str::startsWith($attachment, 'storage/documentos/')) {
            $attachment = Str::after($attachment, 'storage/documentos/');
        } elseif (Str::startsWith($attachment, 'documentos/')) {
            $attachment = Str::after($attachment, 'documentos/');
        }

        if (Str::endsWith($attachment, '/')) {
            return filled($fileName) ? $attachment . $fileName : null;
        }

        return $attachment;
    }

    private function resolveDocumentFilename(Document $record, string $path): string
    {
        return $record->title ?? basename($path) ?? ('documento-' . $record->getKey() . '.pdf');
    }

    private function resolveDocumentViewUrl(Document $record): ?string
    {
        $path = $this->resolveDocumentStoragePath($record);

        if (!$path || !Storage::disk('documentos')->exists($path)) {
            return null;
        }

        return Storage::disk('documentos')->url($path);
    }

    private function extractFirstString(mixed $value): ?string
    {
        if (is_string($value)) {
            $value = trim($value);

            if ($value === '') {
                return null;
            }

            if (Str::startsWith($value, ['[', '{'])) {
                $decoded = json_decode($value, true);

                if (json_last_error() === JSON_ERROR_NONE) {
                    return $this->extractFirstString($decoded);
                }
            }

            return $value;
        }

        if (is_array($value)) {
            foreach ($value as $item) {
                $itemValue = $this->extractFirstString($item);
                if (filled($itemValue)) {
                    return $itemValue;
                }
            }
        }

        return null;
    }
}
