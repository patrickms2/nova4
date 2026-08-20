<?php

namespace Tests\Feature\Facturas;

use App\Services\Facturacion\PdfFacturaImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImportarPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_importa_factura_desde_pdf(): void
    {
        $pdfPath = '/Users/patrickms/Downloads/00000038_26.pdf';

        if (! file_exists($pdfPath)) {
            $this->markTestSkipped('PDF de muestra no encontrado: '.$pdfPath);
        }

        Storage::fake('local');

        $file = new UploadedFile(
            $pdfPath,
            '00000038_26.pdf',
            'application/pdf',
            null,
            true
        );

        $importer = new PdfFacturaImporter;
        $result = $importer->import($file);

        $this->assertNotNull($result['factura_id']);
        $this->assertNotNull($result['codfactura']);
        $this->assertSame('COOPERATIVA TAXISTAS NORTE Y SUR DE LANZAROTE SC', $result['cliente_nombre'], 'Nombre parseado: '.$result['cliente_nombre']);
        $this->assertGreaterThanOrEqual(1, $result['lineas']);
    }
}
