@extends('layouts.app')

@section('title', 'Taxi confirmado | TAXILANZ Demo')
@section('meta_description', 'Pantalla de taxi confirmado con recomendaciones contextuales y decisiones rápidas en TAXILANZ Demo.')

@section('content')
@php
    $primaryRecommendation = $recommendations->first();
    $secondaryRecommendations = $recommendations->skip(1);
@endphp
<section class="hero">
    <article class="card hero-copy hero-copy--compact">
        <div>
            <span class="eyebrow">Taxi confirmado</span>
            <h1>Tu taxi llega en {{ $ride->eta_minutes ?? 6 }} min.</h1>
            <p class="compact-lead">Decide una sola cosa más, si te interesa. El resto queda fuera.</p>
        </div>

        <div class="meta-row">
            <span class="meta-pill">{{ $ride->pickup_label }}</span>
            <span class="meta-pill">{{ $ride->destination_label }}</span>
            <span class="meta-pill">{{ ucfirst($ride->source_channel) }}</span>
            <span class="meta-pill">{{ $ride->context_zone ?: 'Zona activa' }}</span>
        </div>

        <div class="story-rail" aria-label="Progreso del funnel">
            <span class="story-chip is-done">1 · Taxi</span>
            <span class="story-chip is-active">2 · Confirmado</span>
            <span class="story-chip">3 · Oferta</span>
            <span class="story-chip">4 · Reserva</span>
        </div>

        @if ($primaryRecommendation)
            <div class="decision-card">
                <div class="decision-primary decision-primary--accent">
                    <span class="screen-badge">Siguiente mejor opción</span>
                    <h2 class="decision-title">{{ $primaryRecommendation->offer->title }}</h2>
                    <div class="offer-meta">
                        @if($primaryRecommendation->offer->location_label)<span class="pill">{{ $primaryRecommendation->offer->location_label }}</span>@endif
                        @if($primaryRecommendation->offer->price_from)<span class="pill">€{{ number_format((float) $primaryRecommendation->offer->price_from, 0) }}</span>@endif
                        @if($primaryRecommendation->offer->duration_minutes)<span class="pill">{{ $primaryRecommendation->offer->duration_minutes }} min</span>@endif
                        <span class="pill">Disponible ahora</span>
                    </div>
                    <div class="inline-actions">
                        <a class="btn" href="{{ route('offers.show', $primaryRecommendation->offer->slug) }}?ride={{ $ride->id }}&recommendation={{ $primaryRecommendation->id }}">Ver y reservar</a>
                        <button type="button" class="icon-button" data-modal-open="signal-primary" data-tooltip="Señal completa">Ver señal</button>
                    </div>
                </div>

                <details class="disclosure">
                    <summary>
                        <span>ℹ️ ¿Por qué esta opción?</span>
                        <span>Abrir</span>
                    </summary>
                    <div class="disclosure-body">
                        <ul class="accordion-list">
                            <li>Encaja con tu destino: <strong>{{ $ride->destination_label }}</strong>.</li>
                            @if($primaryRecommendation->offer->location_label)
                                <li>Está cerca de <strong>{{ $primaryRecommendation->offer->location_label }}</strong> y se puede decidir rápido.</li>
                            @endif
                            @if($primaryRecommendation->reason)
                                <li>{{ $primaryRecommendation->reason }}</li>
                            @else
                                <li>La priorizamos por cercanía, disponibilidad y facilidad de cierre.</li>
                            @endif
                        </ul>
                    </div>
                </details>
            </div>

            <dialog class="app-modal" id="signal-primary" aria-labelledby="signal-primary-title">
                <div class="app-modal-card">
                    <div class="app-modal-head">
                        <div>
                            <p class="eyebrow">Señal completa</p>
                            <h2 id="signal-primary-title">Por qué {{ $primaryRecommendation->offer->title }} sube arriba</h2>
                        </div>
                        <button type="button" class="app-modal-close" data-modal-close aria-label="Cerrar modal">Cerrar</button>
                    </div>
                    <div class="metric-strip metric-strip--2">
                        <div class="metric-cell">
                            <strong>Top {{ $primaryRecommendation->position }}</strong>
                            <span>posición actual</span>
                        </div>
                        <div class="metric-cell">
                            <strong>{{ $ride->eta_minutes ?? 6 }} min</strong>
                            <span>momento de atención</span>
                        </div>
                    </div>
                    <ul class="accordion-list">
                        <li>Trayecto activo: <strong>{{ $ride->pickup_label }}</strong> → <strong>{{ $ride->destination_label }}</strong>.</li>
                        <li>Zona relevante: <strong>{{ $ride->context_zone ?: 'General' }}</strong>.</li>
                        <li>Canal de origen: <strong>{{ ucfirst($ride->source_channel) }}</strong>.</li>
                        <li>{{ $primaryRecommendation->reason ?: 'La propuesta destaca por contexto, proximidad y facilidad de reserva.' }}</li>
                    </ul>
                </div>
            </dialog>
        @else
            <div class="notice">Todavía no hay una propuesta activa para este trayecto.</div>
        @endif
    </article>

    <aside class="phone-shell" aria-label="Vista móvil del taxi confirmado">
        <div class="phone-screen phone-screen--focused">
            <div class="phone-topbar">
                <span>Taxi confirmado</span>
                <span class="phone-dot-group" aria-hidden="true">
                    <span class="phone-dot"></span>
                    <span class="phone-dot"></span>
                    <span class="phone-dot is-live"></span>
                </span>
            </div>

            <div class="route-card">
                <small>Trayecto activo</small>
                <strong>{{ $ride->pickup_label }} → {{ $ride->destination_label }}</strong>
                <span>ETA {{ $ride->eta_minutes ?? 6 }} min · {{ ucfirst($ride->source_channel) }} · {{ $ride->context_zone ?: 'Zona activa' }}</span>
            </div>

            @if ($primaryRecommendation)
                <article class="recommendation-focus">
                    <div class="phone-action-top">
                        <strong class="phone-action-title">{{ $primaryRecommendation->offer->title }}</strong>
                        <span>Top {{ $primaryRecommendation->position }}</span>
                    </div>
                    <small>Disponible ahora</small>
                    <div class="phone-action-meta">
                        @if($primaryRecommendation->offer->location_label)<span>{{ $primaryRecommendation->offer->location_label }}</span>@endif
                        @if($primaryRecommendation->offer->price_from)<span>€{{ number_format((float) $primaryRecommendation->offer->price_from, 0) }}</span>@endif
                        @if($primaryRecommendation->offer->duration_minutes)<span>{{ $primaryRecommendation->offer->duration_minutes }} min</span>@endif
                    </div>
                    <a class="btn" href="{{ route('offers.show', $primaryRecommendation->offer->slug) }}?ride={{ $ride->id }}&recommendation={{ $primaryRecommendation->id }}">Ver y reservar</a>
                </article>
            @endif

            @if ($secondaryRecommendations->isNotEmpty())
                <div class="phone-list">
                    @foreach ($secondaryRecommendations as $recommendation)
                        <a class="phone-action" href="{{ route('offers.show', $recommendation->offer->slug) }}?ride={{ $ride->id }}&recommendation={{ $recommendation->id }}">
                            <div class="phone-action-top">
                                <strong class="phone-action-title">{{ $recommendation->offer->title }}</strong>
                                <span>Top {{ $recommendation->position }}</span>
                            </div>
                            <div class="phone-action-meta">
                                @if($recommendation->offer->location_label)<span>{{ $recommendation->offer->location_label }}</span>@endif
                                @if($recommendation->offer->price_from)<span>€{{ number_format((float) $recommendation->offer->price_from, 0) }}</span>@endif
                                @if($recommendation->offer->duration_minutes)<span>{{ $recommendation->offer->duration_minutes }} min</span>@endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </aside>
</section>

@if ($secondaryRecommendations->isNotEmpty())
    <section class="section">
        <div class="section-head section-head--compact">
            <div>
                <span class="eyebrow">Más opciones</span>
                <h2>Solo si quieres comparar</h2>
            </div>
        </div>

        <div class="cards cards--compact">
            @foreach ($secondaryRecommendations as $recommendation)
                <article class="card offer-card offer-card--compact">
                    <div>
                        <div class="offer-meta">
                            <span class="pill">Top {{ $recommendation->position }}</span>
                            @if($recommendation->offer->location_label)<span class="pill">{{ $recommendation->offer->location_label }}</span>@endif
                            @if($recommendation->offer->price_from)<span class="pill">€{{ number_format((float) $recommendation->offer->price_from, 0) }}</span>@endif
                        </div>
                        <h3>{{ $recommendation->offer->title }}</h3>
                    </div>
                    <div class="inline-actions inline-actions--stack-mobile">
                        <a class="btn btn-secondary" href="{{ route('offers.show', $recommendation->offer->slug) }}?ride={{ $ride->id }}&recommendation={{ $recommendation->id }}">Ver detalle</a>
                    </div>
                    <details class="disclosure disclosure--soft">
                        <summary>
                            <span>ℹ️ Ver motivo</span>
                            <span>Abrir</span>
                        </summary>
                        <div class="disclosure-body">
                            <p>{{ $recommendation->reason ?: 'Opción contextual cercana al destino y fácil de reservar.' }}</p>
                        </div>
                    </details>
                </article>
            @endforeach
        </div>
    </section>
@endif
@endsection
