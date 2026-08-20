<?php

namespace App\Jobs;

use App\Models\Factura;
use App\Services\Facturacion\VeriFactuService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Facades\Log;

class EnviarFacturaVeriFactu implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public int $facturaId,
    ) {}

    public function handle(VeriFactuService $service): void
    {
        $factura = Factura::find($this->facturaId);

        if (! $factura) {
            Log::warning('Factura no encontrada para envío VeriFactu', ['factura_id' => $this->facturaId]);

            return;
        }

        $service->enviar($factura);
    }

    public function middleware(): array
    {
        return [new RateLimited('verifactu')];
    }
}
