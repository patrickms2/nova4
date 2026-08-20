@extends('layouts.app')

@section('title', $offer->title . ' | TAXILANZ Demo')
@section('meta_description', $offer->excerpt ?: 'Detalle de oferta contextual con reserva simple en TAXILANZ Demo.')

@section('content')
@php
    $categoryLabels = [
        'restaurant' => 'Restaurante',
        'experience' => 'Experiencia',
        'activity' => 'Actividad',
        'service' => 'Servicio',
    ];
@endphp
<section class="split">
    <article class="card offer-card">
        <div class="story-rail" aria-label="Progreso del funnel">
            <span class="story-chip is-done">1 · Taxi</span>
            <span class="story-chip is-done">2 · Contexto</span>
            <span class="story-chip is-active">3 · Oferta</span>
            <span class="story-chip">4 · Reserva</span>
        </div>

        <div class="phone-screen phone-screen--focused" style="padding:18px;">
            <div class="phone-topbar">
                <span>Propuesta contextual</span>
                <span class="phone-dot-group" aria-hidden="true">
                    <span class="phone-dot"></span>
                    <span class="phone-dot"></span>
                    <span class="phone-dot is-live"></span>
                </span>
            </div>

            <div class="offer-visual" aria-hidden="true"></div>

            <div class="decision-primary">
                <span class="screen-badge">Detalle oferta</span>
                <h1 style="font-size:clamp(2.15rem,4vw,3.6rem);max-width:13ch;margin-top:14px;">{{ $offer->title }}</h1>
                <div class="offer-meta">
                    <span class="pill">{{ $categoryLabels[$offer->category] ?? ucfirst($offer->category) }}</span>
                    @if($offer->location_label)<span class="pill">{{ $offer->location_label }}</span>@endif
                    @if($offer->price_from)<span class="pill">Desde €{{ number_format((float) $offer->price_from, 0) }}</span>@endif
                    @if($offer->duration_minutes)<span class="pill">{{ $offer->duration_minutes }} min</span>@endif
                    @if($offer->available_now)<span class="pill">Disponible hoy</span>@endif
                </div>
                <div class="inline-actions">
                    <a class="btn" href="#reserve">Reservar ahora</a>
                    @if($recommendation)
                        <button type="button" class="icon-button" data-modal-open="offer-signal" data-tooltip="Señal completa">Ver señal</button>
                    @endif
                </div>
            </div>

            @if($offer->excerpt)
                <p class="screen-copy">{{ $offer->excerpt }}</p>
            @endif

            @if($recommendation || $ride)
                <details class="disclosure disclosure--soft">
                    <summary>
                        <span>ℹ️ ¿Por qué aparece ahora?</span>
                        <span>Abrir</span>
                    </summary>
                    <div class="disclosure-body">
                        <ul class="accordion-list">
                            @if($ride)
                                <li>Llega después de un trayecto hacia <strong>{{ $ride->destination_label }}</strong>.</li>
                            @endif
                            @if($offer->location_label)
                                <li>Está alineada con la zona <strong>{{ $offer->location_label }}</strong>.</li>
                            @endif
                            <li>{{ $recommendation?->reason ?: 'Se prioriza por proximidad, timing y facilidad de decisión.' }}</li>
                        </ul>
                    </div>
                </details>
            @endif
        </div>
    </article>

    <aside class="card form-card" id="reserve">
        <span class="eyebrow">Reserva</span>
        <h2>Confirma y sigue.</h2>
        <p class="compact-lead">Solo pedimos lo necesario para cerrar la decisión.</p>

        @if ($errors->any())
            <div class="errors">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="post" action="{{ route('bookings.store', $offer) }}" style="margin-top:10px;">
            @csrf
            <input type="hidden" name="ride_id" value="{{ $ride?->id }}">
            <input type="hidden" name="ride_recommendation_id" value="{{ $recommendation?->id }}">

            <label>
                <span class="field-label">Nombre</span>
                <input type="text" name="customer_name" value="{{ old('customer_name', 'Alex Morgan') }}" required>
            </label>

            <div class="grid-2">
                <label>
                    <span class="field-label">Email</span>
                    <input type="email" name="customer_email" value="{{ old('customer_email', 'alex@example.com') }}">
                </label>
                <label>
                    <span class="field-label">Teléfono</span>
                    <input type="text" name="customer_phone" value="{{ old('customer_phone', '+34 600 123 456') }}">
                </label>
            </div>

            <div class="grid-2">
                <label>
                    <span class="field-label">Personas</span>
                    <select name="party_size">
                        @for ($i = 1; $i <= 8; $i++)
                            <option value="{{ $i }}" @selected((int) old('party_size', 2) === $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </label>
                <label>
                    <span class="field-label">Fecha / hora</span>
                    <input type="datetime-local" name="booking_for" value="{{ old('booking_for') }}">
                </label>
            </div>

            <label>
                <span class="field-label">Nota</span>
                <textarea name="notes">{{ old('notes', 'Mesa tranquila si está disponible.') }}</textarea>
            </label>

            <button class="btn" type="submit">Confirmar reserva</button>
        </form>

        <details class="disclosure" style="margin-top:8px;">
            <summary>
                <span>ℹ️ Ver detalle de atribución</span>
                <span>Abrir</span>
            </summary>
            <div class="disclosure-body">
                <ul class="accordion-list">
                    <li>La reserva se vincula al trayecto si existe <code>ride_id</code>.</li>
                    <li>Si vienes desde una recomendación, también queda guardada su posición.</li>
                    <li>Eso permite enseñar conversión y comisión en admin sin ruido en la UI principal.</li>
                </ul>
            </div>
        </details>
    </aside>
</section>

@if($recommendation)
    <dialog class="app-modal" id="offer-signal" aria-labelledby="offer-signal-title">
        <div class="app-modal-card">
            <div class="app-modal-head">
                <div>
                    <p class="eyebrow">Señal completa</p>
                    <h2 id="offer-signal-title">Lectura contextual</h2>
                </div>
                <button type="button" class="app-modal-close" data-modal-close aria-label="Cerrar modal">Cerrar</button>
            </div>
            <div class="metric-strip metric-strip--3">
                <div class="metric-cell">
                    <strong>Top {{ $recommendation->position }}</strong>
                    <span>posición</span>
                </div>
                <div class="metric-cell">
                    <strong>{{ $ride?->eta_minutes ?? '—' }}</strong>
                    <span>ETA del taxi</span>
                </div>
                <div class="metric-cell">
                    <strong>{{ $ride?->context_zone ?: ($offer->location_label ?: 'General') }}</strong>
                    <span>zona activa</span>
                </div>
            </div>
            <ul class="accordion-list">
                <li>{{ $recommendation->reason ?: 'La propuesta aparece por contexto, proximidad y capacidad de cierre.' }}</li>
                @if($ride)
                    <li>Trayecto vinculado: <strong>{{ $ride->pickup_label }}</strong> → <strong>{{ $ride->destination_label }}</strong>.</li>
                @endif
                <li>Oferta con ticket visible y reserva rápida.</li>
            </ul>
        </div>
    </dialog>
@endif
@endsection
