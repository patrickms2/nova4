<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mock P24 Checkout — symulacja sandbox</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
        body { background: #f5f7fa; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: white; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); max-width: 480px; width: 100%; padding: 40px; }
        .badge { display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 9999px; font-size: 11px; font-weight: 600; letter-spacing: 0.05em; text-transform: uppercase; margin-bottom: 16px; }
        h1 { font-size: 22px; color: #111827; margin-bottom: 8px; }
        p.lead { color: #6b7280; font-size: 14px; line-height: 1.5; margin-bottom: 24px; }
        dl { background: #f9fafb; border-radius: 8px; padding: 16px 20px; margin-bottom: 24px; }
        dl div { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e5e7eb; }
        dl div:last-child { border-bottom: none; }
        dt { color: #6b7280; font-size: 13px; }
        dd { color: #111827; font-weight: 500; font-size: 14px; }
        .amount { font-size: 18px; font-weight: 700; color: #059669; }
        .actions { display: flex; gap: 12px; flex-direction: column; }
        button { width: 100%; padding: 14px 20px; border: none; border-radius: 8px; font-size: 15px; font-weight: 600; cursor: pointer; transition: opacity 0.2s; }
        button:hover { opacity: 0.9; }
        .btn-pay { background: #059669; color: white; }
        .btn-cancel { background: white; color: #dc2626; border: 1px solid #fecaca; }
        form { margin: 0; }
        .footer { text-align: center; margin-top: 24px; font-size: 11px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Mock Sandbox P24</span>
        <h1>Symulacja platnosci</h1>
        <p class="lead">To jest mock sandboxa P24 dla srodowiska testowego. Wybierz akcje aby zasymulowac wynik platnosci.</p>

        <dl>
            <div>
                <dt>Numer rezerwacji</dt>
                <dd style="font-family: monospace;">{{ substr($rental->id, 0, 8) }}</dd>
            </div>
            <div>
                <dt>Klient</dt>
                <dd>{{ $rental->name }}</dd>
            </div>
            <div>
                <dt>Termin</dt>
                <dd>{{ $rental->start_date->format('d.m.Y') }} – {{ $rental->end_date->format('d.m.Y') }}</dd>
            </div>
            <div>
                <dt>Kwota</dt>
                <dd class="amount">{{ $amount }} {{ $currency }}</dd>
            </div>
        </dl>

        <div class="actions">
            <form method="POST" action="{{ $confirmUrl }}">
                @csrf
                <button type="submit" class="btn-pay">Zaplac (sukces)</button>
            </form>
            <form method="POST" action="{{ $cancelUrl }}">
                @csrf
                <button type="submit" class="btn-cancel">Anuluj platnosc</button>
            </form>
        </div>

        <p class="footer">srodowisko testowe — brak prawdziwej transakcji</p>
    </div>
</body>
</html>
