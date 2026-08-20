<div>
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

    $html = view('pdf.factura-novagestion', $data)->render();

    return response(
        Browsershot::html($html)
            ->format('A4')
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->pdf()
    )->header('Content-Type', 'application/pdf');
}</div>
