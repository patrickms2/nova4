<?php

namespace Tests\Feature\Facturas;

use App\Jobs\EnviarFacturaVeriFactu;
use App\Models\Empresa;
use App\Models\Factura;
use App\Services\Facturacion\VeriFactuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Config;
use Mockery;
use App\Contracts\VeriFactuInvoice;
use App\Services\AeatClient;
use Tests\TestCase;

class FacturaVeriFactuTest extends TestCase
{
    use RefreshDatabase;

    public function test_factura_implementa_verifactu_invoice(): void
    {
        $factura = Factura::factory()->create();

        $this->assertInstanceOf(VeriFactuInvoice::class, $factura);
        $this->assertSame($factura->codfactura, $factura->getInvoiceNumber());
        $this->assertSame('F1', $factura->getInvoiceType());
        $this->assertGreaterThanOrEqual(1, $factura->getBreakdowns()->count());
    }

    public function test_previous_verifactu_returns_last_invoice_with_hash(): void
    {
        $empresa = Empresa::factory()->create();

        $first = Factura::factory()->create([
            'empresa_id' => $empresa->id,
            'fechaemitido' => now()->subDays(2),
            'verifactu_hash' => 'hash-first',
        ]);

        $second = Factura::factory()->create([
            'empresa_id' => $empresa->id,
            'fechaemitido' => now()->subDay(),
        ]);

        $this->assertTrue($second->previousVeriFactu()->is($first));
    }

    public function test_is_verifactu_sent(): void
    {
        $sent = Factura::factory()->create(['verifactu_status' => 'sent']);
        $accepted = Factura::factory()->create(['verifactu_status' => 'accepted']);
        $pending = Factura::factory()->create(['verifactu_status' => null]);

        $this->assertTrue($sent->isVeriFactuSent());
        $this->assertTrue($accepted->isVeriFactuSent());
        $this->assertFalse($pending->isVeriFactuSent());
    }

    public function test_service_envia_factura_y_guarda_hash(): void
    {
        Config::set('verifactu.issuer.name', 'Emisor Test');
        Config::set('verifactu.issuer.vat', 'B12345678');

        $empresa = Empresa::factory()->create(['nif' => 'B12345678']);
        $factura = Factura::factory()->create(['empresa_id' => $empresa->id]);

        $client = Mockery::mock(AeatClient::class);
        $client->shouldReceive('sendInvoice')
            ->once()
            ->with(Mockery::on(fn ($f) => $f->is($factura)), Mockery::any())
            ->andReturn([
                'status' => 'success',
                'hash' => 'fake-hash',
                'request' => '<xml>request</xml>',
                'response' => '<xml>response</xml>',
            ]);

        $service = new VeriFactuService($client);
        $result = $service->enviar($factura);

        $factura->refresh();

        $this->assertSame('success', $result['status']);
        $this->assertSame('sent', $factura->verifactu_status);
        $this->assertSame('fake-hash', $factura->verifactu_hash);
        $this->assertNotNull($factura->verifactu_qr_url);
    }

    public function test_service_marca_rechazado_cuando_aeat_falla(): void
    {
        Config::set('verifactu.issuer.name', 'Emisor Test');
        Config::set('verifactu.issuer.vat', 'B12345678');

        $factura = Factura::factory()->create();

        $client = Mockery::mock(AeatClient::class);
        $client->shouldReceive('sendInvoice')
            ->once()
            ->andReturn([
                'status' => 'error',
                'message' => 'SOAP fault',
                'request' => '<xml>request</xml>',
                'response' => '<xml>response</xml>',
            ]);

        $service = new VeriFactuService($client);
        $result = $service->enviar($factura);

        $factura->refresh();

        $this->assertSame('error', $result['status']);
        $this->assertSame('rejected', $factura->verifactu_status);
        $this->assertSame('SOAP fault', $factura->verifactu_response_message);
    }

    public function test_service_salta_si_ya_esta_enviada(): void
    {
        $factura = Factura::factory()->create(['verifactu_status' => 'sent']);

        $client = Mockery::mock(AeatClient::class);
        $client->shouldNotReceive('sendInvoice');

        $service = new VeriFactuService($client);
        $result = $service->enviar($factura);

        $this->assertSame('skipped', $result['status']);
    }

    public function test_job_dispatches(): void
    {
        Bus::fake([EnviarFacturaVeriFactu::class]);

        $factura = Factura::factory()->create();

        EnviarFacturaVeriFactu::dispatch($factura->id);

        Bus::assertDispatched(EnviarFacturaVeriFactu::class, fn ($job) => $job->facturaId === $factura->id);
    }
}
