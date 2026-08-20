@extends('layouts.app')

@section('title', 'TAXILANZ Demo | Taxi, recomendaciones contextuales y reserva simple')
@section('meta_description', 'Demo TAXILANZ en Laravel: el taxi funciona como punto de entrada para activar 2–3 propuestas relevantes y convertirlas en reservas medibles.')

@section('content')
    @php
        $primaryOffer = $featuredOffers->first();
        $secondaryOffer = $featuredOffers->skip(1)->first();
    @endphp
    <section class="hero">
        <article class="card hero-copy">
            <div>
                <span class="eyebrow">Laravel-first demo · TAXILANZ</span>
                <h1>El taxi se convierte en el inicio de una reserva útil.</h1>
                <p>
                    Esta demo cuenta una historia muy concreta: un turista pide taxi, el trayecto se confirma,
                    aparecen 2–3 propuestas relevantes y una de ellas se convierte en reserva con tracking real.
                    Sin marketplace. Sin catálogo infinito. Solo activación en el momento exacto.
                </p>
            </div>

            <div class="stats">
                <div class="stat"><strong>{{ $metrics['rides'] }}</strong><span>Solicitudes de taxi</span></div>
                <div class="stat"><strong>{{ $metrics['views'] }}</strong><span>Vistas de recomendación</span></div>
                <div class="stat"><strong>{{ $metrics['bookings'] }}</strong><span>Reservas cerradas</span></div>
            </div>
        </article>
        <aside class="phone-shell" aria-label="Vista previa móvil de TAXILANZ">
            <div class="phone-screen">
                <div class="phone-topbar">
                    <span>{{ now()->format('H:i') }} · Patrick</span>
                    <span class="phone-dot-group" aria-hidden="true">
                    <span class="phone-dot"></span>
                    <span class="phone-dot"></span>
                    <span class="phone-dot is-live"></span>
                </span>
                </div>

                <span class="screen-badge">Storyboard activo</span>
                <h2 class="screen-title">4 pantallas, 1 historia</h2>
                <p class="screen-copy">La demo ahora se entiende como producto móvil real: pedir taxi, esperar, decidir
                    una propuesta y cerrar la reserva.</p>

                <div class="route-card">
                    <small>Punto de entrada</small>
                    <strong>Aeropuerto César Manrique → Puerto del Carmen</strong>
                    <span>Primero movilidad. Después contexto. Y solo al final, conversión atribuible.</span>
                </div>

                <div class="screen-kpis">
                    <div class="screen-kpi"><strong>01</strong><span>taxi</span></div>
                    <div class="screen-kpi"><strong>02</strong><span>contexto</span></div>
                    <div class="screen-kpi"><strong>04</strong><span>booking</span></div>
                </div>

                <div class="phone-list">
                    <div class="phone-list-item">
                        <div class="phone-list-row">
                            <strong>Pedir taxi</strong>
                            <span>start</span>
                        </div>
                        <small>Necesidad inmediata</small>
                    </div>
                    <div class="phone-list-item">
                        <div class="phone-list-row">
                            <strong>{{ $primaryOffer?->title ?: 'Oferta contextual' }}</strong>
                            <span>next</span>
                        </div>
                        <small>{{ $primaryOffer?->location_label ?: 'Zona activa' }}</small>
                    </div>
                </div>

                <a class="btn" href="#request">Activar demo</a>
            </div>
        </aside>
        <aside class="phone-shell" aria-label="Vista previa móvil de TAXILANZ">
            <div class="phone-screen">
                <div class="phone-topbar">
                    <span>{{ now()->format('H:i') }} · Lanzarote</span>
                    <span class="phone-dot-group" aria-hidden="true">
                    <span class="phone-dot"></span>
                    <span class="phone-dot"></span>
                    <span class="phone-dot is-live"></span>
                </span>
                </div>

                <span class="screen-badge">Storyboard activo</span>
                <h2 class="screen-title">4 pantallas, 1 historia</h2>
                <p class="screen-copy">La demo ahora se entiende como producto móvil real: pedir taxi, esperar, decidir
                    una propuesta y cerrar la reserva.</p>

                <div class="route-card">
                    <small>Punto de entrada</small>
                    <strong>Aeropuerto César Manrique → Puerto del Carmen</strong>
                    <span>Primero movilidad. Después contexto. Y solo al final, conversión atribuible.</span>
                </div>

                <div class="screen-kpis">
                    <div class="screen-kpi"><strong>01</strong><span>taxi</span></div>
                    <div class="screen-kpi"><strong>02</strong><span>contexto</span></div>
                    <div class="screen-kpi"><strong>04</strong><span>booking</span></div>
                </div>

                <div class="phone-list">
                    <div class="phone-list-item">
                        <div class="phone-list-row">
                            <strong>Pedir taxi</strong>
                            <span>start</span>
                        </div>
                        <small>Necesidad inmediata</small>
                    </div>
                    <div class="phone-list-item">
                        <div class="phone-list-row">
                            <strong>{{ $primaryOffer?->title ?: 'Oferta contextual' }}</strong>
                            <span>next</span>
                        </div>
                        <small>{{ $primaryOffer?->location_label ?: 'Zona activa' }}</small>
                    </div>
                </div>

                <a class="btn" href="#request">Activar demo</a>
            </div>
        </aside>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <span class="eyebrow">4-screen mobile storyboard</span>
                <h2>La demo se lee en 20 segundos</h2>
            </div>
            <p>Este bloque baja la abstracción: enseña exactamente qué ve el turista y por qué eso termina en ingreso
                atribuible.</p>
        </div>

        <div class="storyboard-grid">
            <article class="card storyboard-card">
                <span class="eyebrow">Pantalla 1</span>
                <div class="storyboard-phone">
                    <span class="storyboard-step">Taxi first</span>
                    <h3 style="margin:0;">Pedir taxi</h3>
                    <p class="storyboard-caption">Acción principal clarísima, pocos campos y cero fricción para entrar
                        en el funnel.</p>
                    <div class="storyboard-mini-list">
                        <div class="storyboard-mini-item">
                            <strong>Aeropuerto → hotel</strong>
                            <span>Origen y destino bastan para empezar.</span>
                        </div>
                        <div class="storyboard-mini-item">
                            <strong>Canal hotel / app</strong>
                            <span>También deja señal de origen para ventas.</span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="card storyboard-card">
                <span class="eyebrow">Pantalla 2</span>
                <div class="storyboard-phone">
                    <span class="storyboard-step is-warm">Momento exacto</span>
                    <h3 style="margin:0;">Taxi confirmado</h3>
                    <p class="storyboard-caption">Con ETA visible aparece la ventana de atención correcta: no catálogo,
                        solo 2–3 decisiones útiles.</p>
                    <div class="storyboard-mini-list">
                        <div class="storyboard-mini-item">
                            <strong>{{ $metrics['views'] }} vistas</strong>
                            <span>La pantalla ya activa interés medible.</span>
                        </div>
                        <div class="storyboard-mini-item">
                            <strong>{{ $metrics['clicks'] }} clics</strong>
                            <span>Intención real mientras el taxi llega.</span>
                        </div>
                    </div>
                </div>
            </article>

            <article class="card storyboard-card">
                <span class="eyebrow">Pantalla 3</span>
                <div class="storyboard-phone">
                    <span class="storyboard-step">Oferta contextual</span>
                    <h3 style="margin:0;">Propuesta relevante</h3>
                    <p class="storyboard-caption">La mejor oferta se presenta como continuidad natural del trayecto, no
                        como un escaparate infinito.</p>
                    <div class="storyboard-mini-list">
                        <div class="storyboard-mini-item">
                            <strong>{{ $primaryOffer?->title ?: 'Oferta destacada' }}</strong>
                            <span>{{ $primaryOffer?->location_label ?: 'Zona activa' }} · {{ $primaryOffer?->price_from ? 'Desde €'.number_format((float) $primaryOffer->price_from, 0) : 'Ticket consultable' }}</span>
                        </div>
                        @if($secondaryOffer)
                            <div class="storyboard-mini-item">
                                <strong>{{ $secondaryOffer->title }}</strong>
                                <span>{{ $secondaryOffer->duration_minutes ? $secondaryOffer->duration_minutes.' min' : 'Plan rápido' }} · alternativa secundaria</span>
                            </div>
                        @endif
                    </div>
                </div>
            </article>

            <article class="card storyboard-card">
                <span class="eyebrow">Pantalla 4</span>
                <div class="storyboard-phone">
                    <span class="storyboard-step is-success">Conversión</span>
                    <h3 style="margin:0;">Reserva y atribución</h3>
                    <p class="storyboard-caption">La historia termina con dinero visible: booking, GMV demo y comisión
                        estimada en el panel.</p>
                    <div class="storyboard-mini-list">
                        <div class="storyboard-mini-item">
                            <strong>{{ $metrics['bookings'] }} reservas</strong>
                            <span>{{ $metrics['ride_to_booking_rate'] }}% ride → booking</span>
                        </div>
                        <div class="storyboard-mini-item">
                            <strong>€{{ number_format($metrics['commission'], 0) }}</strong>
                            <span>Comisión estimada ya presentable en admin.</span>
                        </div>
                    </div>
                </div>
            </article>
        </div>
    </section>

    <section class="section">
        <div class="proof-grid">
            <article class="card proof-card">
                <span class="eyebrow">Impacto partner</span>
                <strong class="proof-stat">{{ $metrics['ride_to_booking_rate'] }}%</strong>
                <h2>Conversión ride → booking</h2>
                <p>La demo enseña que un trayecto confirmado puede convertirse en ingreso atribuible, no solo en
                    transporte resuelto.</p>
            </article>

            <article class="card proof-card">
                <span class="eyebrow">Señal comercial</span>
                <strong class="proof-stat">€{{ number_format($metrics['commission'], 0) }}</strong>
                <h2>Comisión estimada trazable</h2>
                <p>Desde el panel puedes enseñar GMV demo, comisión y etapas del funnel sin depender de discurso
                    abstracto.</p>
            </article>

            <article class="card proof-card">
                <span class="eyebrow">Ángulo ventas</span>
                <strong class="proof-stat">{{ $metrics['clicks'] }}</strong>
                <h2>Clics con intención real</h2>
                <p>Menos catálogo, más contexto. Ese es el argumento que entienden hotel, recepción y operador
                    local.</p>
                <a class="inline-link" href="/admin/login">Abrir panel demo</a>
            </article>
        </div>
    </section>

    <section id="request" class="split">
        <article class="card form-card">
            <span class="eyebrow">1 · Solicitar taxi</span>
            <h2>Activa un trayecto demo</h2>
            <p class="muted">Usa un origen y destino realistas de Lanzarote para que el motor pueda puntuar mejor las
                propuestas.</p>

            @if ($errors->any())
                <div class="errors">
                    <strong>Revisa el formulario:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="form-note">
                Acción principal first. Cuatro campos, cero fricción y suficiente señal para contar la historia completa
                en móvil.
            </div>

            <form method="post" action="{{ route('rides.store') }}" style="margin-top:18px;">
                @csrf
                <div class="grid-2">
                    <label>
                        <span class="field-label">Punto de recogida</span>
                        <input type="text" name="pickup_label"
                               value="{{ old('pickup_label', 'Aeropuerto César Manrique') }}"
                               placeholder="Aeropuerto César Manrique" required>
                    </label>
                    <label>
                        <span class="field-label">Destino</span>
                        <input type="text" name="destination_label"
                               value="{{ old('destination_label', 'Puerto del Carmen') }}"
                               placeholder="Puerto del Carmen, Marina, Playa Blanca..." required>
                    </label>
                </div>
                <div class="grid-2">
                    <label>
                        <span class="field-label">Programado para</span>
                        <input type="datetime-local" name="scheduled_for" value="{{ old('scheduled_for') }}">
                    </label>
                    <label>
                        <span class="field-label">Canal de origen</span>
                        <select name="source_channel" required>
                            @foreach (['web' => 'Web', 'hotel' => 'Hotel', 'reception' => 'Recepción', 'app' => 'App'] as $value => $label)
                                <option
                                    value="{{ $value }}" @selected(old('source_channel', 'hotel') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
                <input type="hidden" name="locale" value="es">
                <button class="btn" type="submit">Confirmar taxi y activar sugerencias</button>
            </form>
        </article>

        <aside id="how" class="card">
            <span class="eyebrow">Qué demuestra esta demo</span>
            <h2>Flujo MVP real</h2>
            <div class="storyboard-progress">
                <div class="storyboard-progress-step is-done">
                    <strong>1 · Taxi</strong>
                    <span>Entrada simple que captura contexto y canal.</span>
                </div>
                <div class="storyboard-progress-step is-active">
                    <strong>2 · Espera útil</strong>
                    <span>La ETA abre un micro-momento de atención.</span>
                </div>
                <div class="storyboard-progress-step">
                    <strong>3 · Oferta</strong>
                    <span>Solo la propuesta con más probabilidad de cierre.</span>
                </div>
                <div class="storyboard-progress-step">
                    <strong>4 · Booking</strong>
                    <span>Conversión rastreable hasta comisión.</span>
                </div>
            </div>
        </aside>
    </section>

    <section class="section">
        <div class="section-head">
            <div>
                <span class="eyebrow">Inventario demo</span>
                <h2>Ofertas listas para sugerir</h2>
            </div>
            <p>Contenido corto, accionable y relevante para un turista que ya ha pedido transporte.</p>
        </div>

        <div class="cards">
            @foreach ($featuredOffers as $offer)
                <article class="card offer-card">
                    <div class="offer-visual" aria-hidden="true"></div>
                    <div>
                        <div class="offer-meta">
                            <span class="pill">{{ ucfirst($offer->category) }}</span>
                            @if($offer->location_label)
                                <span class="pill">{{ $offer->location_label }}</span>
                            @endif
                            @if($offer->price_from)
                                <span class="pill">Desde €{{ number_format((float) $offer->price_from, 0) }}</span>
                            @endif
                        </div>
                        <h3 style="margin-top:14px;">{{ $offer->title }}</h3>
                        <p>{{ $offer->excerpt }}</p>
                    </div>
                </article>
            @endforeach
        </div>
    </section>

    <section class="section">
        <article class="card"
                 style="display:flex;justify-content:space-between;align-items:center;gap:18px;flex-wrap:wrap;">
            <div>
                <span class="eyebrow">Cierre demo</span>
                <h2>Front simple para turista. Historia clara para negocio.</h2>
                <p class="muted">Cuando termines el recorrido, entra en el panel para enseñar funnel, canales y reservas
                    sin salir de la narrativa TAXILANZ.</p>
            </div>
            <a class="btn btn-secondary" href="/admin/login">Ir al dashboard Filament</a>
        </article>
    </section>
@endsection
