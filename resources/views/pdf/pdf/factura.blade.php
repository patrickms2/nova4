<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->codfactura }}</title>
    <style>
        @page {
            margin: 1.2cm 1.5cm;
        }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .rounded-box {
            border: 1px solid #bbb;
            border-radius: 16px;
            padding: 12px 18px;
            margin-bottom: 12px;
        }
        .text-orange {
            color: #df8026;
        }
        .bg-orange {
            background-color: #df8026;
        }
        .table-lines {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 10.5px;
        }
        .table-lines th {
            color: #df8026;
            font-weight: bold;
            text-align: left;
            padding: 6px 4px;
            font-size: 11px;
        }
        .table-lines td {
            padding: 8px 4px;
            vertical-align: top;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .nota-final {
            text-align: center;
            font-size: 9.5px;
            color: #555;
            margin-top: 15px;
        }
    </style>
</head>
<body>

    <!-- Box 1: Cabecera Emisor -->
    <div class="rounded-box">
        <table style="width: 100%; border-collapse: collapse; border: none;">
            <tr>
                <td style="width: 30%; vertical-align: middle; border: none;">
                    @if(file_exists(public_path('logo_h2.jpg')))
                        <img src="{{ public_path('logo_h2.jpg') }}" style="height: 52px; display: block;" alt="Logo">
                    @else
                        <div style="font-weight: bold; font-size: 16px; color: #df8026;">NOVAGESTIÓN</div>
                    @endif
                </td>
                <td style="width: 30%; text-align: center; vertical-align: middle; border: none;">
                    <span style="font-size: 18px; font-weight: bold; color: #df8026; letter-spacing: 1px;">FACTURA</span>
                </td>
                <td style="width: 40%; text-align: right; font-size: 9.5px; line-height: 1.35; color: #444; vertical-align: middle; border: none;">
                    <div style="font-weight: bold; font-size: 10.5px; color: #333; margin-bottom: 2px;">Patrick Axel Müller Suárez</div>
                    <div>NIF: 45532522CC</div>
                    <div>Piragua, 3 - Costa Teguise 35509</div>
                    <div>Lanzarote - España</div>
                    <div>T: +34646426442 / E: patrickms@gmail.com</div>
                    <div>IBAN ES69 1583 0001 1990 9448 5695</div>
                    <div style="font-size: 9px;">SWIFT / BIC: REVOESM2</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Box 2: Datos Factura y Cliente -->
    <div class="rounded-box">
        <table style="width: 100%; border-collapse: collapse; border: none; font-size: 11px; line-height: 1.6;">
            <tr>
                <td style="width: 55%; vertical-align: top; border: none;">
                    <div><strong>Nº:</strong> {{ $factura->codfactura }}</div>
                    <div style="margin-top: 3px;"><strong>Observaciones:</strong> {{ $factura->observaciones ?? '' }}</div>
                    <div style="margin-top: 10px;"><strong>Cliente:</strong> {{ $factura->cliente_nombre ?? optional($factura->cliente)->nombretotal }}</div>
                    <div style="margin-top: 3px;"><strong>Dirección:</strong> {{ $factura->cliente_direccion ?? optional($factura->cliente)->domicilio }}</div>
                    <div style="margin-top: 15px;"><strong>Teléfonos:</strong> {{ $factura->cliente_telefono ?? optional($factura->cliente)->telefono }}</div>
                </td>
                <td style="width: 45%; vertical-align: top; text-align: right; border: none;">
                    <div><strong>Fecha emisión:</strong> {{ optional($factura->fechaemitido)->format('d.m.Y') }}</div>
                    <div style="margin-top: 10px; font-weight: bold;">CIF: {{ $factura->cliente_cif ?? optional($factura->cliente)->dni }}</div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Tabla de Líneas de Facturación -->
    <table class="table-lines">
        <thead>
            <tr style="border-bottom: 2px solid #df8026;">
                <th style="width: 12%;">Fecha</th>
                <th style="width: 43%;">Descripción</th>
                <th class="text-center" style="width: 6%;">Cant.</th>
                <th class="text-center" style="width: 7%;">Unidad</th>
                <th class="text-right" style="width: 8%;">Precio</th>
                <th class="text-center" style="width: 6%;">Dto.</th>
                <th class="text-center" style="width: 6%;">Imp.</th>
                <th class="text-center" style="width: 6%;">Ret.</th>
                <th class="text-right" style="width: 10%;">Importe</th>
            </tr>
        </thead>
        <tbody>
            @foreach($registros as $linea)
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="white-space: nowrap;">
                        {{ optional($linea->fecha ?? $factura->fechaemitido)->format('d.m.Y') }}
                    </td>
                    <td style="line-height: 1.35; padding-right: 10px;">
                        <div style="font-weight: bold; color: #111;">{{ $linea->descripcion }} ({{ $linea->concepto_id }})</div>
                        @if(!empty($linea->observaciones))
                            <div style="font-size: 9px; color: #666; margin-top: 2px;">{{ $linea->observaciones }}</div>
                        @endif
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->cantidad ?? 1, 0) }}
                    </td>
                    <td class="text-center">
                        {{ $linea->unidad ?? '1' }}
                    </td>
                    <td class="text-right">
                        {{ number_format($linea->precio ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->descuento ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->valorimpuesto ?? 7, 0, ',', '.') }}
                    </td>
                    <td class="text-center">
                        {{ number_format($linea->valorretenciones ?? 15, 0, ',', '.') }}
                    </td>
                    <td class="text-right" style="font-weight: bold;">
                        {{ number_format($linea->importe ?? 0, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Box 3: Totales -->
    <div class="rounded-box" style="margin-top: 30px;">
        <table style="width: 100%; border-collapse: collapse; border: none; font-size: 11px; line-height: 1.6;">
            <tr>
                <td style="width: 35%; vertical-align: middle; border: none;">
                    <div><strong>B. Exenta:</strong> {{ number_format($factura->baseexenta ?? 0, 2, ',', '.') }}</div>
                    <div style="margin-top: 3px;"><strong>B. Imponible 7%:</strong> {{ number_format($factura->baseimponible ?? 0, 2, ',', '.') }}</div>
                </td>
                <td style="width: 35%; vertical-align: middle; border: none; padding-left: 15px;">
                    <div><strong>Retenciones:</strong> {{ number_format($factura->retenciones ?? 0, 2, ',', '.') }}</div>
                    <div style="margin-top: 3px;"><strong>IGIC 7%:</strong> {{ number_format($factura->impuesto ?? 0, 2, ',', '.') }}</div>
                </td>
                <td style="width: 30%; vertical-align: middle; text-align: right; border: none; font-size: 14px;">
                    <strong>Importe:</strong> <span style="color: #df8026; font-size: 17px; font-weight: bold; margin-left: 5px;">{{ number_format($factura->importe ?? 0, 2, ',', '.') }} €</span>
                </td>
            </tr>
        </table>
    </div>

    <!-- Nota Pie de Página -->
    <div class="nota-final">
        Régimen Especial del Criterio de caja. La factura se entenderá pagada cuando conste el abono del importe total.
    </div>

</body>
</html>
