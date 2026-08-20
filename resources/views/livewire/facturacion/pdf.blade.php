@php
    $emisor = config('facturacion.emisor');
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->codfactura }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; }
        .emisor { font-size: 11px; line-height: 1.4; }
        .emisor-strong { font-weight: bold; font-size: 12px; }
        .titulo-factura { font-size: 20px; font-weight: bold; text-align: right; margin-top: -10px; }
        .meta { margin-top: 15px; font-size: 11px; }
        .meta div { margin-bottom: 2px; }
        .tabla-lineas { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .tabla-lineas th, .tabla-lineas td { border: 1px solid #000; padding: 4px 3px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totales { margin-top: 12px; width: 50%; margin-left: auto; font-size: 11px; }
        .totales div { display: flex; justify-content: space-between; margin-bottom: 2px; }
        .nota { margin-top: 10px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="emisor">
        <div class="emisor-strong">{{ $emisor['nombre'] ?? '' }}</div>
        <div>NIF: {{ $emisor['nif'] ?? '' }}</div>
        <div>{{ $emisor['direccion'] ?? '' }}</div>
        <div>{{ $emisor['cp_localidad'] ?? '' }}</div>
        <div>T: {{ $emisor['telefono'] ?? '' }} / E: {{ $emisor['email'] ?? '' }}</div>
        <div>IBAN {{ $emisor['iban'] ?? '' }}</div>
        <div>SWIFT / BIC: {{ $emisor['bic'] ?? '' }}</div>
    </div>

    <div class="titulo-factura">FACTURA</div>

    <div class="meta">
        <div><strong>Fecha emisión:</strong> {{ optional($factura->fechaemitido)->format('d.m.Y') }}</div>
        <div><strong>Nº:</strong> {{ $factura->codfactura }}</div>
        <div><strong>Cliente:</strong> {{ $factura->cliente_nombre ?? $factura->codcliente }}</div>
        @if(!empty($factura->cliente_cif))
            <div><strong>CIF:</strong> {{ $factura->cliente_cif }}</div>
        @endif
        @if(!empty($factura->cliente_direccion))
            <div><strong>Dirección:</strong> {{ $factura->cliente_direccion }}</div>
        @endif
        @if(!empty($factura->cliente_telefono))
            <div><strong>Teléfonos:</strong> {{ $factura->cliente_telefono }}</div>
        @endif
    </div>

    <table class="tabla-lineas">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Unidad</th>
                <th class="right">Precio</th>
                <th class="right">Dto.</th>
                <th class="right">Imp.</th>
                <th class="right">Ret.</th>
                <th class="right">Importe</th>
                <th>Descripción</th>
                <th class="center">Cant.</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $linea)
                <tr>
                    <td>{{ optional($linea->fecha ?? $factura->fechaemitido)->format('d.m.Y') }}</td>
                    <td>{{ $linea->unidad ?? 'UNID' }}</td>
                    <td class="right">{{ number_format($linea->precio ?? 0, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($linea->descuento ?? 0, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($linea->valorimpuesto ?? 0, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($linea->valorretenciones ?? 0, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($linea->importe ?? 0, 2, ',', '.') }}</td>
                    <td>{{ $linea->descripcion }}</td>
                    <td class="center">{{ number_format($linea->cantidad ?? 1, 0) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $importe      = $factura->importe ?? $registros->sum('importe');
        $baseImponible = $factura->baseimponible ?? 0;
        $baseExenta   = $factura->baseexenta ?? 0;
        $igic         = $factura->impuesto ?? 0;
        $retenciones  = $factura->retenciones ?? 0;
    @endphp

    <div class="totales">
        <div><span>Importe:</span><span>{{ number_format($importe, 2, ',', '.') }} €</span></div>
        <div><span>B. Imponible:</span><span>{{ number_format($baseImponible, 2, ',', '.') }}</span></div>
        <div><span>B. Exenta:</span><span>{{ number_format($baseExenta, 2, ',', '.') }}</span></div>
        <div><span>IGIC:</span><span>{{ number_format($igic, 2, ',', '.') }}</span></div>
        <div><span>Retenciones:</span><span>{{ number_format($retenciones, 2, ',', '.') }}</span></div>
    </div>

    <div class="nota">Régimen Especial del Criterio de caja</div>
</body>
</html>
