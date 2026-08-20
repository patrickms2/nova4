<?php

namespace Tests\Feature\Novafactu;

use App\Mcp\Servers\NovaFactuServer;
use App\Mcp\Tools\NovaFactu\CreateInvoiceTool;
use App\Mcp\Tools\NovaFactu\ListClientsTool;
use App\Mcp\Tools\NovaFactu\ListCompaniesTool;
use App\Mcp\Tools\NovaFactu\ListConceptsTool;
use App\Mcp\Tools\NovaFactu\ListExpensesTool;
use App\Mcp\Tools\NovaFactu\ListInvoicesTool;
use App\Mcp\Tools\NovaFactu\SendInvoicePdfTool;
use App\Mail\FacturaPdfMail;
use App\Models\Cliente;
use App\Models\Concepto;
use App\Models\Empresa;
use App\Models\Factura;
use App\Models\Gasto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NovaFactuToolsTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_invoice_tool_creates_factura_with_lines_and_totals(): void
    {
        $cliente = Cliente::factory()->create();

        $response = NovaFactuServer::tool(CreateInvoiceTool::class, [
            'cliente_id' => $cliente->id,
            'fechaemitido' => '2026-07-01',
            'notas' => 'Factura de prueba MCP',
            'lineas' => [
                [
                    'descripcion' => 'Servicio de transporte',
                    'cantidad' => 2,
                    'precio' => 100,
                    'impuesto' => 7,
                    'retenciones' => 15,
                ],
            ],
        ]);

        $response->assertOk();

        $factura = Factura::query()->latest('id')->first();

        $this->assertNotNull($factura);
        $this->assertSame($cliente->id, $factura->cliente_id);
        $this->assertNotEmpty($factura->codfactura);
        $this->assertEquals(200.0, (float) $factura->baseimponible);
        $this->assertEquals(14.0, (float) $factura->impuesto);
        $this->assertEquals(30.0, (float) $factura->retenciones);
        $this->assertEquals(184.0, (float) $factura->importe);
        $this->assertSame(1, $factura->registros()->count());

        $response->assertSee($factura->codfactura);
    }

    public function test_create_invoice_tool_requires_cliente_and_lines(): void
    {
        $response = NovaFactuServer::tool(CreateInvoiceTool::class, [
            'lineas' => [],
        ]);

        $response->assertHasErrors();
        $this->assertSame(0, Factura::query()->count());
    }

    public function test_create_invoice_tool_defaults_lines_from_client_conceptos_and_previous_month(): void
    {
        $cliente = Cliente::factory()->create(['nombretotal' => 'PUBLIGESTION SL']);

        Concepto::create([
            'codconcepto' => 'CON0001',
            'cliente_id' => $cliente->id,
            'concepto' => 'Servicio de gestión',
            'unidad' => 'UNID',
            'precio' => 150,
            'descuento' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
        ]);

        $response = NovaFactuServer::tool(CreateInvoiceTool::class, [
            'cliente' => 'PUBLIGESTION',
            'fechaemitido' => '2026-07-07',
        ]);

        $response->assertOk();

        $factura = Factura::query()->latest('id')->first();
        $registro = $factura->registros()->first();

        $this->assertSame('Junio', $registro->descripcion);
        $this->assertEquals(150.0, (float) $registro->precio);
        $this->assertEquals(15.0, (float) $registro->retenciones);
        $this->assertEquals(150.0, (float) $factura->baseimponible);
        $this->assertEquals(10.5, (float) $factura->impuesto);
        $this->assertEquals(22.5, (float) $factura->retenciones);
        $this->assertEquals(138.0, (float) $factura->importe);
    }

    public function test_create_invoice_tool_fails_when_client_has_no_conceptos_and_no_lines(): void
    {
        $cliente = Cliente::factory()->create();

        $response = NovaFactuServer::tool(CreateInvoiceTool::class, [
            'cliente_id' => $cliente->id,
        ]);

        $response->assertHasErrors();
        $this->assertSame(0, Factura::query()->count());
    }

    public function test_create_invoice_tool_line_defaults_from_concepto_id(): void
    {
        $cliente = Cliente::factory()->create();

        $concepto = Concepto::create([
            'codconcepto' => 'CON0002',
            'cliente_id' => $cliente->id,
            'concepto' => 'Cuota mensual',
            'unidad' => 'MES',
            'precio' => 200,
            'descuento' => 0,
            'impuesto' => 7,
            'retenciones' => 15,
        ]);

        $response = NovaFactuServer::tool(CreateInvoiceTool::class, [
            'cliente_id' => $cliente->id,
            'fechaemitido' => '2026-03-15',
            'lineas' => [
                ['concepto_id' => $concepto->id],
            ],
        ]);

        $response->assertOk();

        $registro = Factura::query()->latest('id')->first()->registros()->first();

        $this->assertSame('Cuota mensual', $registro->descripcion);
        $this->assertSame('MES', $registro->unidad);
        $this->assertEquals(200.0, (float) $registro->precio);
        $this->assertEquals(15.0, (float) $registro->retenciones);
    }

    public function test_list_invoices_tool_filters_by_search(): void
    {
        $cliente = Cliente::factory()->create(['nombretotal' => 'Cooperativa Taxis Norte']);
        $factura = Factura::factory()->create(['cliente_id' => $cliente->id]);
        Factura::factory()->create();

        $response = NovaFactuServer::tool(ListInvoicesTool::class, [
            'search' => 'Cooperativa Taxis Norte',
        ]);

        $response->assertOk();
        $response->assertSee($factura->codfactura);
        $response->assertSee('"count":1');
    }

    public function test_list_clients_tool_filters_by_search(): void
    {
        Cliente::factory()->create(['nombretotal' => 'PUBLIGESTIÓN CANARIAS, S.L']);
        Cliente::factory()->create(['nombretotal' => 'Otro Cliente SL']);

        $response = NovaFactuServer::tool(ListClientsTool::class, [
            'search' => 'PUBLIGESTIÓN',
        ]);

        $response->assertOk();
        $response->assertSee('PUBLIGESTIÓN CANARIAS, S.L');
        $response->assertSee('"count":1');
    }

    public function test_list_concepts_tool_filters_by_cliente(): void
    {
        $cliente = Cliente::factory()->create();
        $otro = Cliente::factory()->create();

        Concepto::create([
            'codconcepto' => 'CON0003',
            'cliente_id' => $cliente->id,
            'concepto' => 'Mantenimiento web',
            'precio' => 250,
            'impuesto' => 7,
            'retenciones' => 15,
        ]);

        Concepto::create([
            'codconcepto' => 'CON0004',
            'cliente_id' => $otro->id,
            'concepto' => 'Otro servicio',
            'precio' => 100,
            'impuesto' => 7,
            'retenciones' => 0,
        ]);

        $response = NovaFactuServer::tool(ListConceptsTool::class, [
            'cliente_id' => $cliente->id,
        ]);

        $response->assertOk();
        $response->assertSee('Mantenimiento web');
        $response->assertSee('"count":1');
    }

    public function test_list_companies_tool_returns_companies(): void
    {
        Empresa::factory()->create(['empresa' => 'NovaFact Empresa Test']);

        $response = NovaFactuServer::tool(ListCompaniesTool::class, []);

        $response->assertOk();
        $response->assertSee('NovaFact Empresa Test');
    }

    public function test_list_expenses_tool_filters_by_estado(): void
    {
        Gasto::factory()->create(['descripcion' => 'Alquiler oficina julio', 'estado' => 'pendiente']);
        Gasto::factory()->create(['estado' => 'pagado']);

        $response = NovaFactuServer::tool(ListExpensesTool::class, [
            'estado' => 'pendiente',
        ]);

        $response->assertOk();
        $response->assertSee('Alquiler oficina julio');
        $response->assertSee('"count":1');
    }

    public function test_send_invoice_pdf_tool_sends_mail_to_given_email(): void
    {
        Mail::fake();

        $factura = Factura::factory()->create();

        $response = NovaFactuServer::tool(SendInvoicePdfTool::class, [
            'factura_id' => $factura->id,
            'email' => 'patrickms@gmail.com',
        ]);

        $response->assertOk();
        $response->assertSee('patrickms@gmail.com');

        Mail::assertSent(FacturaPdfMail::class, function (FacturaPdfMail $mail) use ($factura): bool {
            return $mail->factura->is($factura) && $mail->hasTo('patrickms@gmail.com');
        });
    }

    public function test_send_invoice_pdf_tool_resolves_by_codfactura_and_defaults_to_client_email(): void
    {
        Mail::fake();

        $cliente = Cliente::factory()->create(['email' => 'cliente@example.com']);
        $factura = Factura::factory()->create(['cliente_id' => $cliente->id]);

        $response = NovaFactuServer::tool(SendInvoicePdfTool::class, [
            'codfactura' => $factura->codfactura,
        ]);

        $response->assertOk();
        $response->assertSee('cliente@example.com');

        Mail::assertSent(FacturaPdfMail::class, fn (FacturaPdfMail $mail): bool => $mail->hasTo('cliente@example.com'));
    }

    public function test_send_invoice_pdf_tool_fails_for_unknown_codfactura(): void
    {
        Mail::fake();

        $response = NovaFactuServer::tool(SendInvoicePdfTool::class, [
            'codfactura' => 'NO_EXISTE_9999',
        ]);

        $response->assertHasErrors();
        Mail::assertNothingSent();
    }
}
