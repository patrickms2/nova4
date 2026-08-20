<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment {{ $status === 'ok' ? 'completed' : 'failed' }}</title>
</head>
<body>
    <h1>{{ $status === 'ok' ? 'Pago completado' : 'Pago fallido' }}</h1>
    @if (isset($request->bundle_reference))
        <p>Bundle: {{ $request->bundle_reference }}</p>
        <p>Order: {{ $request->redsys_order }}</p>
        <p>Status: {{ $request->payment_status ?? '-' }}</p>
        <p><a href="{{ route('public.bundle') }}">Volver al bundle</a></p>
    @else
        <p>Reference: {{ $request->request_reference }}</p>
        <p>Order: {{ $request->payment_order }}</p>
        <p>Status: {{ $request->payment_status ?? '-' }}</p>
        <p><a href="{{ route('public.explore') }}">Back to explore</a></p>
    @endif
</body>
</html>

