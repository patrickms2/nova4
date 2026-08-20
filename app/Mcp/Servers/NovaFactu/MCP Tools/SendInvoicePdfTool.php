<?php

namespace App\Mcp\Tools\NovaFactu;

use App\Mail\FacturaPdfMail;
use App\Models\Factura;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Mail;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Genera el PDF de una factura de NovaFact y lo envía por email como adjunto. La factura se indica por ID o por número (codfactura). Si no se indica email, se envía al email del cliente o, en su defecto, a patrickms@gmail.com. Devuelve un resumen del envío en JSON.')]
class SendInvoicePdfTool extends Tool
{
    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'factura_id' => 'nullable|integer|exists:facturas,id',
            'codfactura' => 'nullable|string',
            'email' => 'nullable|email',
        ]);

        $factura = $this->resolveFactura($validated);

        if (! $factura instanceof Factura) {
            return $factura;
        }

        $email = $validated['email']
            ?? $factura->cliente?->email
            ?? 'patrickms@gmail.com';

        Mail::to($email)->send(new FacturaPdfMail($factura));

        return Response::json([
            'enviado' => true,
            'factura_id' => $factura->id,
            'codfactura' => $factura->codfactura,
            'cliente' => $factura->cliente?->nombretotal,
            'importe' => (float) $factura->importe,
            'email' => $email,
            'adjunto' => 'Factura-'.$factura->codfactura.'.pdf',
        ]);
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'factura_id' => $schema->integer()->description('ID de la factura a enviar. Alternativa a "codfactura".'),
            'codfactura' => $schema->string()->description('Número de la factura (p. ej. "00041_2026"). Alternativa a "factura_id".'),
            'email' => $schema->string()->description('Email de destino. Por defecto, el email del cliente o patrickms@gmail.com.'),
        ];
    }

    /**
     * Resolve the invoice from its ID or codfactura.
     *
     * @param  array<string, mixed>  $validated
     */
    private function resolveFactura(array $validated): Factura|Response
    {
        if ($validated['factura_id'] ?? null) {
            return Factura::query()->with('cliente', 'registros')->findOrFail($validated['factura_id']);
        }

        $codigo = trim((string) ($validated['codfactura'] ?? ''));

        if ($codigo === '') {
            return Response::error('Debes indicar factura_id o codfactura.');
        }

        $factura = Factura::query()
            ->with('cliente', 'registros')
            ->where('codfactura', $codigo)
            ->first();

        if (! $factura) {
            return Response::error("No se encontró ninguna factura con el número \"{$codigo}\".");
        }

        return $factura;
    }
}
