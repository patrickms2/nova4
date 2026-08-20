<?php

namespace Tests\Feature\Facturas;

use App\Livewire\Facturas\Facturas;
use App\Models\Factura;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GenerarPdfFacturasTest extends TestCase
{
    use RefreshDatabase;

    public function test_genera_pdfs_de_facturas_seleccionadas(): void
    {
        $factura = Factura::factory()->create();
        $filename = public_path('facturas-pdf/'.$factura->codfactura.'.pdf');

        try {
            Livewire::test(Facturas::class)
                ->set('selectedFacturas', [$factura->id => true])
                ->call('generarPdfSeleccionadas')
                ->assertDispatched('toast');

            $this->assertFileExists($filename);
        } finally {
            @unlink($filename);
        }
    }

    public function test_genera_el_pdf_de_una_factura(): void
    {
        $factura = Factura::factory()->create();
        $filename = public_path('facturas-pdf/'.$factura->codfactura.'.pdf');

        try {
            Livewire::test(Facturas::class)
                ->call('crearPdf', $factura->id)
                ->assertDispatched('download-pdf')
                ->assertDispatched('toast');

            $this->assertFileExists($filename);
            $this->assertStringContainsString('/Subtype /Image', file_get_contents($filename));
        } finally {
            @unlink($filename);
        }
    }
}
