@php
    $empresa = $factura->empresa ?? null;
    $emisor = [
        'nombre'       => $empresa->empresa ?? config('facturacion.emisor.nombre'),
        'nif'          => $empresa->nif ?? config('facturacion.emisor.nif'),
        'direccion'    => $empresa->direccion ?? config('facturacion.emisor.direccion'),
        'cp_localidad' => $empresa->poblacion ?? config('facturacion.emisor.cp_localidad'),
        'telefono'     => $empresa->telefono ?? config('facturacion.emisor.telefono'),
        'email'        => $empresa->email ?? config('facturacion.emisor.email'),
        'iban'         => $empresa->cuentacorriente ?? config('facturacion.emisor.iban'),
        'bic'          => config('facturacion.emisor.bic'),
        'logo'         => $empresa->logoempresa ?? null,
        'web'          => $empresa->web ?? null,
    ];
@endphp
    <!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Factura {{ $factura->codfactura }}</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 20px;
        }
        .header {
            width: 100%;
            margin-bottom: 12px;
        }
        .header-left {
            float: left;
            width: 50%;
        }
        .header-right {
            float: right;
            width: 50%;
            text-align: right;
        }
        .logo {
            max-height: 45px;
            margin-bottom: 6px;
        }
        .empresa-nombre {
            font-size: 13px;
            font-weight: bold;
        }
        .empresa-linea {
            margin: 1px 0;
        }
        .clearfix {
            clear: both;
        }
        .titulo-factura {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            margin-top: 5px;
            margin-bottom: 8px;
        }
        .datos-block {
            width: 100%;
            margin-bottom: 12px;
        }
        .datos-cliente, .datos-factura {
            width: 48%;
            display: inline-block;
            vertical-align: top;
            font-size: 10px;
        }
        .box {
            border: 0.5px solid #444;
            padding: 6px;
            border-radius: 2px;
        }
        .box-title {
            font-weight: bold;
            margin-bottom: 4px;
            font-size: 10px;
        }
        .datos-row {
            margin-bottom: 2px;
        }
        .datos-label {
            font-weight: bold;
        }
        table {
            border-collapse: collapse;
            width: 100%;
        }
        .lineas-table th,
        .lineas-table td {
            border: 0.5px solid #444;
            padding: 3px 2px;
        }
        .lineas-table th {
            background: #f0f0f0;
            font-size: 9px;
        }
        .right { text-align: right; }
        .center { text-align: center; }
        .totales {
            margin-top: 10px;
            width: 45%;
            margin-left: auto;
            font-size: 10px;
        }
        .tot-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2px;
        }
        .tot-label {
            font-weight: bold;
        }
        .nota {
            margin-top: 10px;
            font-size: 9px;
        }
        .footer {
            margin-top: 20px;
            border-top: 0.5px solid #aaa;
            padding-top: 5px;
            font-size: 8.5px;
            color: #555;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="header-left">
        @if(!empty($emisor['logo']))
            @php
                $logoPath = public_path('storage/' . $emisor['logo']);
            @endphp
            @if(file_exists($logoPath))
                <img src="{{ $logoPath }}" class="logo" alt="Logo">
            @endif
        @endif
        <div class="empresa-nombre">{{ $emisor['nombre'] }}</div>
        <div class="empresa-linea">NIF: {{ $emisor['nif'] }}</div>
        <div class="empresa-linea">{{ $emisor['direccion'] }}</div>
        <div class="empresa-linea">{{ $emisor['cp_localidad'] }}</div>
        @if($emisor['telefono'])
            <div class="empresa-linea">Tel: {{ $emisor['telefono'] }}</div>
        @endif
        @if($emisor['email'])
            <div class="empresa-linea">Email: {{ $emisor['email'] }}</div>
        @endif
        @if($emisor['web'])
            <div class="empresa-linea">Web: {{ $emisor['web'] }}</div>
        @endif
    </div>
    <div class="header-right">
        <div class="titulo-factura">FACTURA 22</div>
        <div><strong>Nº:</strong> {{ $factura->codfactura }}</div>
        <div><strong>Fecha:</strong> {{ optional($factura->fechaemitido)->format('d.m.Y') }}</div>
    </div>
    <div class="clearfix"></div>
</div>

<div class="datos-block">
    <div class="datos-cliente">
        <div class="box">
            <div class="box-title">Datos del cliente</div>
            <div class="datos-row"><span class="datos-label">Nombre:</span>
                {{ $factura->cliente_nombre ?? optional($factura->cliente)->nombreCorto }}</div>
            @if($factura->cliente_cif || optional($factura->cliente)->dni)
                <div class="datos-row"><span class="datos-label">NIF/CIF:</span>
                    {{ $factura->cliente_cif ?? optional($factura->cliente)->dni }}</div>
            @endif
            @if($factura->cliente_direccion)
                <div class="datos-row"><span class="datos-label">Dirección:</span>
                    {{ $factura->cliente_direccion }}</div>
            @endif
            @if($factura->cliente_telefono)
                <div class="datos-row"><span class="datos-label">Teléfono:</span>
                    {{ $factura->cliente_telefono }}</div>
            @endif
        </div>
    </div>
    <div class="datos-factura">
        <div class="box">
            <div class="box-title">Datos adicionales</div>
            <div class="datos-row"><span class="datos-label">Empresa:</span>
                {{ $empresa->empresa ?? $emisor['nombre'] }}</div>
            @if($emisor['iban'])
                <div class="datos-row"><span class="datos-label">IBAN:</span>
                    {{ $emisor['iban'] }}</div>
            @endif
            @if($emisor['bic'])
                <div class="datos-row"><span class="datos-label">BIC/SWIFT:</span>
                    {{ $emisor['bic'] }}</div>
            @endif
        </div>
    </div>
</div>

<table class="lineas-table">
    <thead>
    <tr>
        <th>Fecha</th>
        <th>Unidad</th>
        <th class="right">Precio</th>
        <th class="right">Dto.</th>
        <th class="right">IGIC</th>
        <th class="right">Ret.</th>
        <th class="right">Importe</th>
        <th>Descripción</th>
        <th class="center">Cant.</th>
    </tr>
    </thead>
    <tbody>
    @foreach($registros as $linea)
        @php
            $fechaLinea = $linea->fecha ?? $factura->fechaemitido;
        @endphp
        <tr>
            <td>{{ optional($fechaLinea)->format('d.m.Y') }}</td>
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
    $base      = $factura->baseimponible ?? $registros->sum('importe');
    $baseEx    = $factura->baseexenta ?? 0;
    $igic      = $factura->impuesto ?? ($base * 0.07);
    $ret       = $factura->retenciones ?? ($base * 0.15);
    $total     = $factura->importe ?? ($base + $igic - $ret);
@endphp

<div class="totales">
    <div class="tot-row">
        <span class="tot-label">Base imponible:</span>
        <span>{{ number_format($base, 2, ',', '.') }} €</span>
    </div>
    <div class="tot-row">
        <span class="tot-label">Base exenta:</span>
        <span>{{ number_format($baseEx, 2, ',', '.') }} €</span>
    </div>
    <div class="tot-row">
        <span class="tot-label">IGIC (7%):</span>
        <span>{{ number_format($igic, 2, ',', '.') }} €</span>
    </div>
    <div class="tot-row">
        <span class="tot-label">Retenciones (15%):</span>
        <span>{{ number_format($ret, 2, ',', '.') }} €</span>
    </div>
    <div class="tot-row">
        <span class="tot-label">Total factura:</span>
        <span>{{ number_format($total, 2, ',', '.') }} €</span>
    </div>
</div>

<div class="nota">
    Régimen Especial del Criterio de caja. La factura se entenderá pagada cuando conste el abono del importe total.
</div>

<div class="footer">
    {{ $emisor['nombre'] }}
    @if($emisor['nif']) · NIF {{ $emisor['nif'] }} @endif
    @if($emisor['direccion']) · {{ $emisor['direccion'] }} @endif
    @if($emisor['cp_localidad']) · {{ $emisor['cp_localidad'] }} @endif
    @if($emisor['telefono']) · Tel: {{ $emisor['telefono'] }} @endif
    @if($emisor['email']) · Email: {{ $emisor['email'] }} @endif
    @if($emisor['web']) · Web: {{ $emisor['web'] }} @endif
</div>
</body>
</html>
