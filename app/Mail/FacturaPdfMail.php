<?php

namespace App\Mail;

use App\Models\Factura;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Mail\Mailable;

class FacturaPdfMail extends Mailable
{
    public function __construct(
        public Factura $factura
    ) {}

    public function build()
    {
        $pdf = Pdf::loadView('pdf.factura', [
            'factura' => $this->factura,
            'registros' => $this->factura->registros,
        ]);

        return $this->from('patrickms@gmail.com', 'Patrick Axel Müller Suárez')
            ->subject('Factura '.$this->factura->codfactura)
            ->view('emails.factura')
            ->with(['factura' => $this->factura])
            ->attachData(
                $pdf->output(),
                'Factura-'.$this->factura->codfactura.'.pdf',
                ['mime' => 'application/pdf']
            );
    }
}
