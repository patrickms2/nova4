<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reserva de ruta taxi</title>
    <meta http-equiv="refresh" content="2;url={{ $draft->chbs_url }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-950 text-white">
    <main class="mx-auto flex min-h-screen max-w-2xl items-center px-6 py-12">
        <section class="w-full rounded-3xl border border-white/10 bg-white/10 p-8 shadow-2xl backdrop-blur">
            <p class="mb-3 text-sm font-semibold uppercase tracking-[0.3em] text-cyan-300">Taxilanz</p>
            <h1 class="text-3xl font-bold">Continuar reserva de taxi</h1>
            <p class="mt-3 text-slate-300">Te llevamos al formulario seguro de rutas para calcular el importe y completar el pago con WooCommerce/Redsys.</p>

            <dl class="mt-8 grid gap-4 rounded-2xl bg-slate-900/70 p-5 text-sm">
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Origen</dt>
                    <dd class="font-medium text-right">{{ $draft->origin }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Destino</dt>
                    <dd class="font-medium text-right">{{ $draft->destination }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Fecha y hora</dt>
                    <dd class="font-medium text-right">{{ $draft->pickup_date?->format('d/m/Y') }} {{ substr((string) $draft->pickup_time, 0, 5) }}</dd>
                </div>
                <div class="flex justify-between gap-4">
                    <dt class="text-slate-400">Personas</dt>
                    <dd class="font-medium text-right">{{ $draft->passengers }}</dd>
                </div>
            </dl>

            <a href="{{ $draft->chbs_url }}" class="mt-8 inline-flex w-full items-center justify-center rounded-2xl bg-cyan-400 px-5 py-4 text-center font-bold text-slate-950 transition hover:bg-cyan-300">
                Abrir formulario de pago
            </a>

            <p class="mt-4 text-center text-xs text-slate-400">Si no se abre automáticamente, pulsa el botón.</p>
        </section>
    </main>
</body>
</html>
