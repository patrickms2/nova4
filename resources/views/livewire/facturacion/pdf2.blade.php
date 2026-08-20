use Spatie\Browsershot\Browsershot;

public function facturaPdf()
{
    $data = [
        'numero' => '000020/25',
        'fecha' => '21.05.2025',
        'observaciones' => 'ABRIL 2025',
        'cliente' => 'MICROMEDIA, S.L.',
        'cif' => 'B15328768',
        'direccion' => 'AVDA DE LUGO, 32, BAIXO',
        'telefono' => '981 570101',
        'lineas' => [
            [
                'fecha' => '21.05.2025',
                'descripcion' => 'MANTENIMIENTO',
                'detalle' => 'Expte AMT-2022-044 -L5 - Abril 2025',
                'cant' => 1,
                'unidad' => 1,
                'precio' => 125,
                'dto' => 0,
                'imp' => 0,
                'ret' => 15,
                'importe' => 106.25,
            ],
            [
                'fecha' => '21.05.2025',
                'descripcion' => 'MANTENIMIENTO',
                'detalle' => 'Expte AMT-2023-0082 - Abril 2025',
                'cant' => 1,
                'unidad' => 1,
                'precio' => 125,
                'dto' => 0,
                'imp' => 0,
                'ret' => 15,
                'importe' => 106.25,
            ],
        ],
    ];

    $html = view('facturacion.pdf2', $data)->render();

    return response(
        Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->pdf()
    )->header('Content-Type', 'application/pdf');
}

<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<style>
    @page { size: A4; margin: 0; }

    body {
        font-family: Arial, sans-serif;
        font-size: 12px;
        color: #222;
        margin: 0;
        background: #fff;
    }

    .page {
        width: 210mm;
        min-height: 297mm;
        padding: 16mm;
        box-sizing: border-box;
        position: relative;
    }

    .header {
        display: flex;
        justify-content: space-between;
        border-bottom: 2px solid #ddd;
        padding-bottom: 14px;
    }

    .brand {
        width: 48%;
        line-height: 1.45;
    }

    .invoice-title {
        text-align: right;
    }

    .invoice-title h1 {
        font-size: 30px;
        margin: 0 0 8px;
        letter-spacing: 1px;
    }

    .box {
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 12px;
        margin-top: 18px;
    }

    .grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 22px;
    }

    th {
        background: #f2f2f2;
        text-align: left;
        padding: 9px;
        border-bottom: 1px solid #ccc;
    }

    td {
        padding: 9px;
        border-bottom: 1px solid #eee;
    }

    .right { text-align: right; }

    .totals {
        margin-left: auto;
        margin-top: 20px;
        width: 42%;
    }

    .totals div {
        display: flex;
        justify-content: space-between;
        padding: 7px 0;
        border-bottom: 1px solid #eee;
    }

    .total-final {
        font-size: 17px;
        font-weight: bold;
    }

    .footer {
        position: absolute;
        bottom: 14mm;
        left: 16mm;
        right: 16mm;
        font-size: 10px;
        color: #666;
        border-top: 1px solid #ddd;
        padding-top: 8px;
    }
</style>
</head>
<body>
<div class="page">

    <div class="header">
        <div class="brand">
            <strong>Patrick Axel Müller Suárez</strong><br>
            NIF: 45532522C<br>
            C/ Piragua, 3 - Costa Teguise<br>
            35509 Lanzarote - España<br>
            T: +34646426442<br>
            E: patrickms@gmail.com<br>
            IBAN ES69 1583 0001 1990 9448 5695<br>
            SWIFT / BIC: REVOESM2
        </div>

        <div class="invoice-title">
            <h1>FACTURA</h1>
            Nº {{ $factura->numero }}<br>
            Fecha: {{ $factura->fecha->format('d/m/Y') }}
        </div>
    </div>

    <div class="grid">
        <div class="box">
            <strong>Cliente</strong><br>
            {{ $factura->cliente->nombre }}<br>
            NIF/CIF: {{ $factura->cliente->nif }}<br>
            {{ $factura->cliente->direccion }}
        </div>

        <div class="box">
            <strong>Datos de pago</strong><br>
            Forma de pago: Transferencia<br>
            Vencimiento: {{ $factura->vencimiento?->format('d/m/Y') }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="right">Cantidad</th>
                <th class="right">Precio</th>
                <th class="right">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($factura->lineas as $linea)
                <tr>
                    <td>{{ $linea->concepto }}</td>
                    <td class="right">{{ $linea->cantidad }}</td>
                    <td class="right">{{ number_format($linea->precio, 2, ',', '.') }} €</td>
                    <td class="right">{{ number_format($linea->total, 2, ',', '.') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>Base imponible</span><strong>{{ number_format($factura->base, 2, ',', '.') }} €</strong></div>
        <div><span>IGIC {{ $factura->igic_porcentaje }}%</span><strong>{{ number_format($factura->igic, 2, ',', '.') }} €</strong></div>
        <div class="total-final"><span>Total</span><span>{{ number_format($factura->total, 2, ',', '.') }} €</span></div>
    </div>

    <div class="footer">
        Factura emitida conforme a la normativa fiscal vigente. Gracias por su confianza.
    </div>

</div>
</body>
</html>