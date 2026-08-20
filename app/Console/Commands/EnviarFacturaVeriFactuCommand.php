<?php

namespace App\Console\Commands;

use App\Jobs\EnviarFacturaVeriFactu;
use App\Models\Factura;
use Illuminate\Console\Command;

class EnviarFacturaVeriFactuCommand extends Command
{
    protected $signature = 'verifactu:enviar {id? : ID de la factura o "pendientes" para enviar todas las no enviadas}';

    protected $description = 'Envia factura(s) a AEAT VeriFactu';

    public function handle(): int
    {
        $argument = $this->argument('id');

        if ($argument === 'pendientes' || $argument === null) {
            $facturas = Factura::query()
                ->whereNull('verifactu_status')
                ->orWhereNotIn('verifactu_status', ['sent', 'accepted'])
                ->get();

            if ($facturas->isEmpty()) {
                $this->info('No hay facturas pendientes de enviar a VeriFactu.');

                return self::SUCCESS;
            }

            foreach ($facturas as $factura) {
                EnviarFacturaVeriFactu::dispatch($factura->id);
            }

            $this->info($facturas->count().' factura(s) encolada(s) para envío.');

            return self::SUCCESS;
        }

        $factura = Factura::find($argument);

        if (! $factura) {
            $this->error('Factura no encontrada.');

            return self::FAILURE;
        }

        EnviarFacturaVeriFactu::dispatch($factura->id);
        $this->info('Factura '.$factura->codfactura.' encolada para envío a VeriFactu.');

        return self::SUCCESS;
    }
}
