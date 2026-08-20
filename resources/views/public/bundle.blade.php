<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bundle La Geria + Lanzaloe | Nova</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #f7f4ef;
            --ink: #15130f;
            --muted: #716b61;
            --line: rgba(21, 19, 15, .1);
            --panel: #fffdf9;
            --accent: #087f5b;
            --accent-dark: #065f43;
            --error: #dc2626;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 540px;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 24px 80px rgba(31, 26, 17, .12);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 24px;
        }
        .brand-mark {
            width: 44px; height: 44px;
            border-radius: 14px;
            background: #e1f3eb;
            color: var(--accent);
            display: grid;
            place-items: center;
            font-weight: 800;
            font-size: 18px;
        }
        h1 {
            margin: 0;
            font-size: 22px;
            font-weight: 800;
        }
        p.subtitle {
            margin: 6px 0 24px;
            color: var(--muted);
            font-size: 14px;
            line-height: 1.5;
        }
        .bundle-summary {
            background: #f6f9f7;
            border: 1px solid #d4e8de;
            border-radius: 16px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .bundle-summary h2 {
            margin: 0 0 12px;
            font-size: 14px;
            font-weight: 700;
            color: var(--accent-dark);
        }
        .bundle-item {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid rgba(8, 127, 91, .12);
            font-size: 14px;
        }
        .bundle-item:last-child { border-bottom: none; }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 12px;
        }
        @media (max-width: 520px) { .form-grid { grid-template-columns: 1fr; } }
        label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 5px;
            color: var(--muted);
        }
        input {
            width: 100%;
            padding: 11px 14px;
            border: 1px solid var(--line);
            border-radius: 12px;
            background: #fff;
            font-size: 14px;
            outline: none;
            transition: border-color .15s, box-shadow .15s;
        }
        input:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(8, 127, 91, .12);
        }
        .field { margin-bottom: 14px; }
        .field.full { grid-column: 1 / -1; }
        button[type="submit"] {
            width: 100%;
            padding: 14px 20px;
            border: none;
            border-radius: 14px;
            background: var(--accent);
            color: #fff;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background .15s;
            margin-top: 8px;
        }
        button[type="submit"]:hover { background: var(--accent-dark); }
        button[type="submit"]:disabled { opacity: .6; cursor: not-allowed; }
        .result {
            margin-top: 20px;
            padding: 16px;
            border-radius: 14px;
            font-size: 14px;
            display: none;
        }
        .result.success { background: #e8f5ef; border: 1px solid #b8e0cc; color: #065f43; }
        .result.error { background: #feeaea; border: 1px solid #f5baba; color: var(--error); }
        .result pre {
            margin: 10px 0 0;
            padding: 10px;
            background: rgba(0,0,0,.04);
            border-radius: 8px;
            overflow-x: auto;
            font-size: 12px;
        }
        .note {
            margin-top: 16px;
            font-size: 12px;
            color: var(--muted);
            text-align: center;
        }
    </style>
</head>
<body>

<div class="card">
    <div class="brand">
        <div class="brand-mark">N</div>
        <div>
            <h1 id="bundleTitle">{{ $bundleProduct?->name ?? 'Bundle La Geria + Lanzaloe' }}</h1>
        </div>
    </div>

    <p class="subtitle" id="bundleDescription">
        {{ $bundleProduct?->description ?? 'Reserva tu visita guiada a Bodega La Geria y añade un producto Lanzaloe en un solo pedido cruzado.' }}
    </p>

    @if ($bundleProducts->count() > 1)
        <div class="field full" style="margin-bottom: 20px;">
            <label for="bundleSelector">Selecciona un bundle</label>
            <select id="bundleSelector" class="bundle-selector" onchange="if(this.value) location.href='{{ route('public.bundle') }}?ref='+encodeURIComponent(this.value)">
                <option value="">-- Elige una opción --</option>
                @foreach ($bundleProducts as $product)
                    <option value="{{ $product->reference ?? $product->id }}" @selected(($bundleProduct?->id === $product->id) || ($bundleProduct?->reference && $bundleProduct->reference === $product->reference))>
                        {{ $product->name }} — {{ number_format($product->total_price, 2, ',', '.') }} {{ $product->currency }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    <div class="bundle-summary" id="bundleSummary" data-bundle='@json($bundleProduct ?? $bundleProducts->first())'>
        <h2>Tu bundle</h2>
        <div id="bundleLines">
            @php
                $product = $bundleProduct ?? $bundleProducts->first();
            @endphp
            @if ($product)
                <div class="bundle-item">
                    <span>{{ $product->la_geria_product_name ?? 'Producto La Geria' }}</span>
                    <strong data-qty-la="{{ $product->la_geria_quantity }}">{{ $product->la_geria_quantity }} × {{ number_format($product->la_geria_unit_price, 2, ',', '.') }} {{ $product->currency }}</strong>
                </div>
                <div class="bundle-item">
                    <span>{{ $product->lanzaloe_product_name ?? 'Producto Lanzaloe' }}</span>
                    <strong data-qty-lanz="{{ $product->lanzaloe_quantity }}">{{ $product->lanzaloe_quantity }} × {{ number_format($product->lanzaloe_unit_price, 2, ',', '.') }} {{ $product->currency }}</strong>
                </div>
                <div class="bundle-item" id="bundleTotal" style="font-weight: 700; border-top: 1px solid rgba(8,127,91,.12); margin-top: 8px; padding-top: 14px;">
                    <span>Total estimado</span>
                    <span data-total="{{ $product->total_price }}">{{ number_format($product->total_price, 2, ',', '.') }} {{ $product->currency }}</span>
                </div>
            @else
                <p>No hay bundles configurados.</p>
            @endif
        </div>
    </div>

    @if (! $bundleProduct && $bundleProducts->isNotEmpty())
        <div class="field full" style="margin-bottom: 18px;">
            <label>Cantidad La Geria</label>
            <input type="number" id="la_geria_quantity_input" value="{{ $bundleProducts->first()->la_geria_quantity }}" min="1" style="max-width: 120px;">
        </div>
        <div class="field full" style="margin-bottom: 18px;">
            <label>Cantidad Lanzaloe</label>
            <input type="number" id="lanzaloe_quantity_input" value="{{ $bundleProducts->first()->lanzaloe_quantity }}" min="1" style="max-width: 120px;">
        </div>
    @endif

    <form id="bundleForm" action="{{ route('public.bundle.store') }}" method="POST">
        <div class="form-grid">
            <div class="field">
                <label for="first_name">Nombre</label>
                <input type="text" id="first_name" name="first_name" value="Prueba" required>
            </div>
            <div class="field">
                <label for="last_name">Apellidos</label>
                <input type="text" id="last_name" name="last_name" value="Nova" required>
            </div>
            <div class="field full">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="poc@novagestion.eu" required>
            </div>
            <div class="field">
                <label for="phone">Teléfono</label>
                <input type="tel" id="phone" name="phone" value="600000000" required>
            </div>
            <div class="field">
                <label for="postcode">Código postal</label>
                <input type="text" id="postcode" name="postcode" value="35508" required>
            </div>
            <div class="field full">
                <label for="address">Dirección</label>
                <input type="text" id="address" name="address" value="Piragua, 3, Costa Teguise" required>
            </div>
            <div class="field">
                <label for="city">Ciudad</label>
                <input type="text" id="city" name="city" value="Costa Teguise" required>
            </div>
            <div class="field">
                <label for="country">País (ISO)</label>
                <input type="text" id="country" name="country" value="ES" maxlength="2" required>
            </div>
        </div>

        <input type="hidden" name="region_id" value="157">
        <input type="hidden" name="region_code" value="Las Palmas">
        <input type="hidden" name="region" value="Las Palmas">
        <input type="hidden" name="street[]" value="Piragua, 3, Costa Teguise">
        <input type="hidden" name="company" value="Novagestión Consultores, S.L.">

        @php
            $selected = $bundleProduct ?? $bundleProducts->first();
        @endphp
        <input type="hidden" name="la_geria_product_id" id="la_geria_product_id" value="{{ $selected?->la_geria_product_id }}">
        <input type="hidden" name="la_geria_quantity" id="la_geria_quantity" value="{{ $selected?->la_geria_quantity }}">
        <input type="hidden" name="lanzaloe_sku" id="lanzaloe_sku" value="{{ $selected?->lanzaloe_sku }}">
        <input type="hidden" name="lanzaloe_quantity" id="lanzaloe_quantity" value="{{ $selected?->lanzaloe_quantity }}">
        <input type="hidden" name="lanzaloe_shipping_method" value="amstrates7">
        <input type="hidden" name="lanzaloe_shipping_carrier" value="amstrates">
        <input type="hidden" name="lanzaloe_payment_method" value="banktransfer">

        <button type="submit" id="submitBtn">Crear pedido cruzado</button>
    </form>

    <div id="result" class="result"></div>
    <p class="note">Pedidos de prueba. En producción se requiere aceptar los términos de Lanzaloe.</p>
</div>

<script>
    const form = document.getElementById('bundleForm');
    const submitBtn = document.getElementById('submitBtn');
    const result = document.getElementById('result');

    form.addEventListener('submit', async function (e) {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Creando pedidos...';
        result.style.display = 'none';
        result.className = 'result';

        const formData = new FormData(form);
        const payload = Object.fromEntries(formData.entries());

        // Convert arrays and numeric fields
        payload.la_geria_product_id = parseInt(payload.la_geria_product_id, 10);
        payload.la_geria_quantity = parseInt(payload.la_geria_quantity, 10);
        payload.lanzaloe_quantity = parseInt(payload.lanzaloe_quantity, 10);
        payload.region_id = parseInt(payload.region_id, 10);
        payload.street = formData.getAll('street[]');

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json();

            if (data.success && data.payment_url) {
                result.style.display = 'block';
                result.classList.add('success');
                result.innerHTML = '<strong>Redirigiendo a Redsys para el pago...</strong>';
                window.location.href = data.payment_url;
                return;
            }

            result.style.display = 'block';
            result.classList.add(data.success ? 'success' : 'error');
            result.innerHTML = `<strong>${data.success ? 'Bundle creado' : 'Bundle parcial'}</strong>` +
                `<pre>${JSON.stringify(data, null, 2)}</pre>`;
        } catch (error) {
            result.style.display = 'block';
            result.classList.add('error');
            result.textContent = 'Error de red: ' + error.message;
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Crear pedido cruzado';
        }
    });
</script>

<script>
    (function () {
        const summary = document.getElementById('bundleSummary');
        if (! summary) return;
        const bundle = JSON.parse(summary.dataset.bundle || '{}');
        const laQtyInput = document.getElementById('la_geria_quantity_input');
        const lanzQtyInput = document.getElementById('lanzaloe_quantity_input');
        const laHidden = document.getElementById('la_geria_quantity');
        const lanzHidden = document.getElementById('lanzaloe_quantity');
        const totalEl = document.querySelector('#bundleTotal span:last-child');

        function render() {
            const laQty = parseInt(laQtyInput?.value || bundle.la_geria_quantity, 10) || 1;
            const lanzQty = parseInt(lanzQtyInput?.value || bundle.lanzaloe_quantity, 10) || 1;
            if (laHidden) laHidden.value = laQty;
            if (lanzHidden) lanzHidden.value = lanzQty;

            const laTotal = laQty * parseFloat(bundle.la_geria_unit_price || 0);
            const lanzTotal = lanzQty * parseFloat(bundle.lanzaloe_unit_price || 0);
            const total = laTotal + lanzTotal;

            if (totalEl) {
                totalEl.textContent = total.toFixed(2).replace('.', ',') + ' ' + (bundle.currency || 'EUR');
            }
        }

        if (laQtyInput) laQtyInput.addEventListener('input', render);
        if (lanzQtyInput) lanzQtyInput.addEventListener('input', render);
        render();
    })();
</script>

</body>
</html>
