<?php

use Livewire\Volt\Component;
use Illuminate\Support\Carbon;

new class extends Component
{
    public ?string $date = null;

    public function calendar_change($date): void
    {
        $this->dispatch('calendar-change', $this->fecha);

    }
};

?>
<style>
    .filter-btn.active .filter-check {
    opacity: 1;
    transform: scale(1);
    display: block !important;
}
.filter-check {
    display: grid;
    width: 20px;
    height: 20px;
    display: none !important;
    place-items: center;
    border: 1px solid
 color-mix(in srgb, var(--filter-color), transparent 48%);
    border-radius: 8px;
    background: 
 color-mix(in srgb, var(--filter-color), white 88%);
    color: var(--filter-color);
    opacity: 0;
    transform: scale(.84);
    transition: opacity .18s ease, transform .18s ease;
}
.filter-btn {
    display: flex;
    min-width: 0;
    min-height: 5px !important;
    cursor: pointer;
    flex-direction: row !important;
    justify-content: space-between;
    border: 1px solid rgba(21, 19, 15, .1);
    border-radius: 18px;
    background: rgba(255, 255, 255, .62);
    padding: 5px !important;
    color: var(--ink);
    text-align: left;
    transition: transform .2s ease, border .2s ease, background .2s ease, box-shadow .2s ease;
}
.open-btn {
    display: inline-flex;
    min-width: 0;
    height: 42px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    gap: 8px;
    border: 1px solid rgba(21, 19, 15, .1);
    border-radius: 999px;
    background: #cb7c62;
    color: #fff;
    padding: 0 14px;
    color: var(--ink);
    font-size: 12px;
    font-weight: 800;
    transition: transform .2s ease, background .2s ease;
}
</style>
<x-layouts.standalone title="Settings — Acme">
        <header class="bg-background/80 supports-[backdrop-filter]:bg-background/60 sticky top-0 z-40 border-b backdrop-blur-xl">
        <div class="mx-auto flex h-16 max-w-5xl items-center gap-3 px-6">
            <a href="/templates/dashboard/raw" class="text-muted-foreground hover:text-foreground inline-flex items-center gap-1 text-sm"><x-lucide-chevron-left class="size-4" /> Back to app</a>
            <div class="ml-auto flex items-center gap-1.5">
                <button type="button" @click="$store.theme && $store.theme.toggle()" class="hover:bg-accent inline-flex size-9 items-center justify-center rounded-md transition-colors" aria-label="Toggle theme"><x-lucide-sun class="size-4 dark:hidden" /><x-lucide-moon class="hidden size-4 dark:block" /></button>
                <x-ui.avatar class="size-8"><x-ui.avatar-fallback>AD</x-ui.avatar-fallback></x-ui.avatar>
            </div>
        </div>
    </header>


<div class="explore-shell">
        <aside class="sidebar">

            <div class="brand-row">
                <a class="brand" href="{{ url('/') }}" aria-label="Go home">
                    <span class="brand-mark">T</span>
                    <span class="brand-text">
                        <span class="brand-title">{{ config('app.name', 'Tourist') }}</span>
                        <span class="brand-subtitle">Public explore map</span>
                    </span>
                </a>
                <a class="home-link" href="{{ url('/') }}" title="Home" aria-label="Home">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m3 11 9-8 9 8"/><path d="M5 10v10h14V10"/><path d="M10 20v-6h4v6"/></svg>
                </a>
            </div>

            <section class="hero-copy">
                <p>Explore hotels, restaurants and taxi services on one live map. </p>
            </section>

            <div class="search-wrap">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input id="searchInput" class="search-input" type="search" placeholder="Search by name, city or service" autocomplete="off">
            </div>

            <div class="filter-grid" aria-label="Explore filters">
                <button class="filter-btn" style="--filter-color: var(--hotel)" data-filter="hotel" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="hotel">0</span>
                    </span>
                    <span class="filter-label">Hotels</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--restaurant)" data-filter="restaurant" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="restaurant">0</span>
                    </span>
                    <span class="filter-label">Restaurants</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--taxi)" data-filter="taxi" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="taxi">0</span>
                    </span>
                    <span class="filter-label">Taxi</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--tour-visit)" data-filter="tour_visit" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="tour_visit">0</span>
                    </span>
                    <span class="filter-label">Visit Tour</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--taxi-route)" data-filter="taxi_route" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="taxi_route">0</span>
                    </span>
                    <span class="filter-label">Taxi Route</span>
                </button>
                <button class="filter-btn" style="--filter-color: var(--transfer)" data-filter="transfer" type="button" role="checkbox" aria-checked="false">
                    <span class="filter-top">
                        <span class="filter-state">
                            <span class="filter-check"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="m5 12 4 4L19 6"/></svg></span>
                            <span class="filter-dot"></span>
                        </span>
                        <span class="filter-count" data-count="transfer">0</span>
                    </span>
                    <span class="filter-label">Transfer</span>
                </button>
            </div>

            <div class="toolbar">
                <button id="fitMapBtn" class="tool-btn" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 3H5a2 2 0 0 0-2 2v3"/><path d="M16 3h3a2 2 0 0 1 2 2v3"/><path d="M8 21H5a2 2 0 0 1-2-2v-3"/><path d="M16 21h3a2 2 0 0 0 2-2v-3"/></svg>
                    Fit map
                </button>
                <button id="nearMeBtn" class="tool-btn" type="button">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v3"/><path d="M12 19v3"/><path d="M2 12h3"/><path d="M19 12h3"/><circle cx="12" cy="12" r="7"/><circle cx="12" cy="12" r="2"/></svg>
                    Near me
                </button>
            </div>

            <div class="results-head">
                <strong>List results</strong>
                <span id="resultsCount">Loading</span>
            </div>
            <div id="resultsList" class="results-list">
                <div class="loading-skeleton"></div>
                <div class="loading-skeleton"></div>
                <div class="loading-skeleton"></div>
            </div>
        </aside>

        <main class="map-stage">
            <div id="exploreMap" aria-label="Public map"></div>
            <div class="map-topbar">
                <div class="floating-summary">
                    <strong id="mapTitle">Live tourism map</strong>
                    <span id="mapSubtitle">Loading public hotels, restaurants and taxi services with real coordinates.</span>
                </div>
                <div class="floating-actions">
                    <button id="clearSelectionBtn" class="icon-button" type="button" title="Clear selection" aria-label="Clear selection">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                    </button>
                </div>
            </div>
            <section id="detailDrawer" class="detail-drawer" aria-live="polite"></section>
        </main>
    </div>

    <section id="requestModal" class="request-modal" aria-hidden="true">
        <div class="request-panel" role="dialog" aria-modal="true" aria-labelledby="requestTitle">

            {{-- ── Sticky header ── --}}
            <div class="request-head">
                <div class="request-head-meta">
                    <span id="requestType" class="type-pill">Request</span>
                    <h2 id="requestTitle">Reserve</h2>
                </div>
                <button id="closeRequestModal" class="icon-button" type="button" aria-label="Close">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" width="18" height="18"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
                </button>
            </div>

            <form id="requestForm" class="request-form" style="display:contents;">

                {{-- ════════════════════════════════════════
                     TOUR VISIT  — 3-step wizard
                     ════════════════════════════════════════ --}}
                <div class="type-fields" data-request-fields="tour">
                    <div
                        data-slot="stepper"
                        x-data="{
                            step: 1,
                            orientation: 'horizontal',
                            tourDate: null,
                            tourSchedule: null,
                            adults: 2,
                            children: 0,
                            get canNext1() { return this.tourDate; },
                            get canNext2() { return this.tourDate && this.tourSchedule; },
                            async goTo(n) {
                                if (n === 3 && window.confirmTourSelectionAvailability) {
                                    const ok = await window.confirmTourSelectionAvailability();
                                    if (!ok) return;
                                }
                                this.step = n;
                                if (n === 3) window.setTourStep && window.setTourStep('contact');
                                if (n === 1) window.setTourStep && window.setTourStep('calendar');
                            },
                            resetWizard() {
                                this.step = 1; this.tourDate = null; this.tourSchedule = null;
                                this.adults = 2; this.children = 0;
                            },
                        }"
                        :data-orientation="orientation"
                        @calendar-change="
                            const val = $event.detail;
                            if (typeof val === 'string') {
                                tourDate = val;
                                document.querySelector('[name=tour_date]').value = val;
                                tourSchedule = null;
                                goTo(2);
                                document.querySelector('[name=tour_schedule]').value = '';
                                window.loadTourSlots && window.loadTourSlots(val);
                            }
                        "
                    >
                        {{-- Step indicator bar --}}
                        <x-ui.stepper-nav class="px-5 pt-4 pb-2">
                            <x-ui.stepper-item :step="1">
                                <x-ui.stepper-trigger><x-ui.stepper-indicator /><x-ui.stepper-title>Fecha</x-ui.stepper-title></x-ui.stepper-trigger>
                                <x-ui.stepper-separator />
                            </x-ui.stepper-item>
                            <x-ui.stepper-item :step="2">
                                <x-ui.stepper-trigger><x-ui.stepper-indicator /><x-ui.stepper-title>Hora</x-ui.stepper-title></x-ui.stepper-trigger>
                                <x-ui.stepper-separator />
                            </x-ui.stepper-item>                            
                            <x-ui.stepper-item :step="3">
                                <x-ui.stepper-trigger><x-ui.stepper-indicator /><x-ui.stepper-title>Personas</x-ui.stepper-title></x-ui.stepper-trigger>
                                <x-ui.stepper-separator />
                            </x-ui.stepper-item>
                            <x-ui.stepper-item :step="4">
                                <x-ui.stepper-trigger><x-ui.stepper-indicator /><x-ui.stepper-title>Datos</x-ui.stepper-title></x-ui.stepper-trigger>
                            </x-ui.stepper-item>                                                      
                        </x-ui.stepper-nav>

                        {{-- STEP 1: choose date then slot --}}
                        <div x-show="step === 1">
                            <div class="request-step-body">
                                <p class="step-label">Step 1 of 4</p>
                                <p class="step-question">When do you want to visit?</p>

                                {{-- BlatUI calendar — event bubbles up to stepper @calendar-change --}}
                                <x-ui.calendar
                                    mode="single"
                                    :min-date="now()->toDateString()"
                                    week-start="1"
                                    locale="en"
                                    class="border-0 shadow-none w-full"
                                />
                                <input name="tour_date" type="hidden" :value="tourDate">


                            </div>
                            <div class="step-nav" x-show="tourDate">
                                <button class="step-btn-next" style="--step-color:var(--tour)" type="button"
                                    :disabled="!canNext1" @click="goTo(2)">
                                    Continue
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- STEP 2: how many people --}}
                        <div x-show="step === 2" x-cloak>
                            <div class="request-step-body">
                                                                {{-- Slot grid (shown once date is chosen) --}}
                                                             <p class="step-label">Step 2 of 3</p>

                                                                <div x-show="tourDate">
                                    <p class="step-label" style="margin-bottom:8px;">Pick a time slot</p>
                                    <div id="tourSlotsPanel" class="tour-slots-panel" aria-live="polite">
                                        <p id="tourSlotHint" class="tour-slot-hint" x-show="!tourSchedule">Loading slots…</p>
                                        <div class="slot-grid tour-slot-grid" aria-label="Tour time slots"></div>
                                    </div>
                                </div>

                                <input name="tour_schedule" type="hidden">
                            <div class="step-nav">
                                <button class="step-btn-back" type="button" @click="goTo(1)" aria-label="Back">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                                </button>
                                <button class="step-btn-next" style="--step-color:var(--tour)" type="button"
                                                                        :disabled="!canNext2" @click="goTo(3)">

                                    Continue
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>

                        <div x-show="step === 3" x-cloak>
                            <div class="request-step-body">
                                <p class="step-label">Step 3 of 3</p>
                                <p class="step-question">How many people?</p>

                                <input name="adults" type="hidden" :value="adults">
                                <input name="children" type="hidden" :value="children">

                                <div>
                                    <p class="step-label" style="margin-bottom:8px;">Adults</p>
                                    <div class="touch-counter">
                                        <button type="button" class="touch-counter-btn" @click="adults = Math.max(1, adults-1)" aria-label="Less adults">−</button>
                                        <span class="touch-counter-value" x-text="adults"></span>
                                        <button type="button" class="touch-counter-btn" @click="adults++" aria-label="More adults">+</button>
                                    </div>
                                </div>

                                <div>
                                    <p class="step-label" style="margin-bottom:8px;">Children <span style="font-weight:500;text-transform:none;">(under 15, free)</span></p>
                                    <div class="touch-counter">
                                        <button type="button" class="touch-counter-btn" @click="children = Math.max(0, children-1)" aria-label="Less children">−</button>
                                        <span class="touch-counter-value" x-text="children"></span>
                                        <button type="button" class="touch-counter-btn" @click="children++" aria-label="More children">+</button>
                                    </div>
                                </div>
                            </div>
                            <div class="step-nav">
                                <button class="step-btn-back" type="button" @click="goTo(2)" aria-label="Back">
                                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                                </button>
                                <button class="step-btn-next" style="--step-color:var(--tour)" type="button" @click="goTo(4)">
                                    Continue
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>



                <div class="summary-fields" data-tour-step="summary">
                    <div class="request-step-body">
                        <p class="step-label">Almost done</p>
                        <label class="field"><span>Fecha de inicio</span><input id="tourStartDate" type="date"  readonly  :value="tourDate" placeholder="Select date"></label>

                        <p class="step-question">Any notes?</p>
                        <label class="field">
                            <span>Notes (optional)</span>
                            <textarea name="notes" placeholder="Arrival time, dietary needs…"></textarea>
                        </label>

                        {{-- Transfer upsell --}}
                        <div id="tourTransferUpsell" style="display:none;">
                            <label style="display:flex;align-items:center;gap:10px;cursor:pointer;padding:12px 0;border-top:1px solid rgba(21,19,15,.08);">
                                <input type="checkbox" id="addTransferToggle" style="width:18px;height:18px;accent-color:#087f5b;flex-shrink:0;">
                                <div>
                                    <div style="font-weight:700;font-size:14px;">Add taxi transfer</div>
                                    <div style="font-size:12px;color:var(--muted);">10% package discount</div>
                                </div>
                            </label>
                            <div id="tourTransferFields" style="display:none;gap:10px;flex-direction:column;">
                                <label class="field"><span>Pickup (hotel / villa)</span><input id="tourTransferPickup" list="transferLocationOptions" placeholder="Hotel Princesa Yaiza" autocomplete="off"></label>
                                <label class="field"><span>Dropoff</span><input id="tourTransferDropoff" value="Bodega La Geria" readonly style="background:#f5f5f5;color:var(--muted);"></label>
                                <label class="field"><span>Pickup time</span><select id="tourTransferPickupTime"><option value="">Select tour time first</option></select></label>
                                <label class="field"><span>Passengers</span><input id="tourTransferPassengers" type="number" min="1" max="16" value="1"></label>
                                <div style="font-size:12px;color:var(--muted);" id="tourTransferPriceInfo"></div>
                            </div>
                        </div>

                        {{-- Price breakdown --}}
                        <div id="priceBreakdown" style="display:none;border-top:1px solid rgba(21,19,15,.08);padding-top:14px;">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;"><span style="font-size:13px;color:var(--muted);">Service subtotal</span><span id="serviceSubtotal" style="font-weight:700;font-size:14px;">--</span></div>
                            <div id="transferPriceRow" style="display:none;justify-content:space-between;align-items:center;margin-bottom:8px;"><span style="font-size:13px;color:var(--muted);">Transfer</span><span id="transferPriceDisplay" style="font-weight:700;font-size:14px;">--</span></div>
                            <div id="discountRow" style="display:none;justify-content:space-between;align-items:center;margin-bottom:8px;"><span style="font-size:13px;color:var(--green);">Package discount</span><span id="discountDisplay" style="font-weight:700;font-size:14px;color:var(--green);">--</span></div>
                            <div style="display:flex;justify-content:space-between;align-items:center;padding-top:8px;border-top:1px solid rgba(21,19,15,.08);"><span style="font-weight:800;font-size:16px;">Total</span><span id="totalPriceDisplay" style="font-weight:900;font-size:20px;color:var(--green);">--</span></div>
                        </div>
                    </div>
                    <div class="step-nav">
                        <button class="step-btn-back" type="button" data-tour-back="contact" aria-label="Back">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                        </button>
                    </div>
                </div>
                </div>

                {{-- ════════════════════════════════════════
                     RESTAURANT — single screen, no calendar
                     ════════════════════════════════════════ --}}
                <div class="type-fields" data-request-fields="restaurant">
                    <div
                        x-data="{ guests: 2 }"
                    >
                        <div class="request-step-body">
                            <p class="step-label">Reservation</p>

                            {{-- Date + time on one row --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <label class="field">
                                    <span>Date</span>
                                    <input name="reservation_date" id="restaurantDateInput" type="date"
                                        :min="new Date().toISOString().slice(0,10)">
                                </label>
                                <label class="field">
                                    <span>Time</span>
                                    <input name="reservation_time" id="restaurantTimeInput" type="time"
                                        step="1800" min="12:00" max="23:00">
                                </label>
                            </div>

                            {{-- Guests counter --}}
                            <div>
                                <p class="step-label" style="margin-bottom:8px;">Guests</p>
                                <div class="touch-counter">
                                    <button type="button" class="touch-counter-btn" @click="guests = Math.max(1, guests-1)">−</button>
                                    <span class="touch-counter-value" x-text="guests"></span>
                                    <button type="button" class="touch-counter-btn" @click="guests = Math.min(30, guests+1)">+</button>
                                </div>
                                <input name="guests" type="hidden" :value="guests">
                            </div>
                        </div>

                        {{-- hidden compat placeholders --}}
                        <div id="restaurantTimePanel" style="display:none;"></div>
                        <div id="restaurantDateSummary" style="display:none;"></div>
                        <div id="restaurantTimeGrid"   style="display:none;"></div>
                    </div>
                </div>

                {{-- ════════════════════════════════════════
                     HOTEL — date range + counters
                     ════════════════════════════════════════ --}}
                <div class="type-fields" data-request-fields="hotel">
                    <div x-data="{ checkin: null, checkout: null, guests: 2, rooms: 1,
                            get nights() { return (this.checkin && this.checkout) ? Math.round((new Date(this.checkout)-new Date(this.checkin))/86400000) : null; } }">
                        <div class="request-step-body">
                            <p class="step-label">Hotel stay</p>

                            {{-- Check-in / Check-out native inputs --}}
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;">
                                <label class="field">
                                    <span>Check-in</span>
                                    <input type="date" name="check_in_date"
                                        :min="new Date().toISOString().slice(0,10)"
                                        @change="checkin = $event.target.value">
                                </label>
                                <label class="field">
                                    <span>Check-out</span>
                                    <input type="date" name="check_out_date"
                                        :min="checkin || new Date().toISOString().slice(0,10)"
                                        @change="checkout = $event.target.value">
                                </label>
                            </div>

                            {{-- Nights badge --}}
                            <p x-show="nights" class="step-label" x-text="nights + ' night' + (nights!==1?'s':'')"></p>

                            {{-- Guests & rooms --}}
                            <div>
                                <p class="step-label" style="margin-bottom:8px;">Guests</p>
                                <div class="touch-counter">
                                    <button type="button" class="touch-counter-btn" @click="guests = Math.max(1, guests-1)">−</button>
                                    <span class="touch-counter-value" x-text="guests"></span>
                                    <button type="button" class="touch-counter-btn" @click="guests++">+</button>
                                </div>
                                <input name="guests" type="hidden" :value="guests">

                                <p class="step-label" style="margin-bottom:8px;margin-top:14px;">Rooms</p>
                                <div class="touch-counter">
                                    <button type="button" class="touch-counter-btn" @click="rooms = Math.max(1, rooms-1)">−</button>
                                    <span class="touch-counter-value" x-text="rooms"></span>
                                    <button type="button" class="touch-counter-btn" @click="rooms++">+</button>
                                </div>
                                <input name="rooms" type="hidden" :value="rooms">
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ════════════════════════════════════════
                     TAXI — datetime + locations
                     ════════════════════════════════════════ --}}
                <div class="type-fields" data-request-fields="taxi">
                    <div class="request-step-body">
                        <p class="step-label">Taxi</p>
                        <p class="step-question">When and where?</p>
                        <label class="field">
                            <span>Pickup date &amp; time</span>
                            <input name="pickup_date_time" type="datetime-local">
                        </label>
                        <label class="field">
                            <span>Pickup address</span>
                            <input name="pickup_address">
                        </label>
                        <label class="field">
                            <span>Dropoff address</span>
                            <input name="dropoff_address">
                        </label>
                        <label class="field">
                            <span>Passengers</span>
                            <input name="passengers" type="number" min="1" max="16" value="1">
                        </label>
                    </div>
                </div>

                {{-- ════════════════════════════════════════
                     TRANSFER — day chips → time → locations
                     ════════════════════════════════════════ --}}
                <div class="type-fields" data-request-fields="transfer">
                    <div class="request-step-body" style="gap:14px;">
                        <p class="step-label">Airport transfer</p>

                        {{-- Day strip (7 days) --}}
                        <div class="transfer-day-grid" id="transferDayGrid" aria-label="Pickup day">
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                            <button class="transfer-day" data-transfer-day type="button" aria-pressed="false"><span></span><span></span></button>
                        </div>

                        {{-- Time slots --}}
                        <div class="transfer-time-panel" id="transferTimePanel">
                            <span class="transfer-time-label">Pickup time</span>
                            <div class="transfer-time-grid" id="transferTimeGrid" aria-label="Pickup time"></div>
                        </div>

                        {{-- Date summary chip --}}
                        <div class="transfer-date-summary" id="transferDateSummary" role="button" tabindex="0" title="Click to change">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>
                            <span id="transferDateSummaryText"></span>
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" style="margin-left:auto;opacity:.5"><path d="M18 6 6 18M6 6l12 12"/></svg>
                        </div>

                        <input name="pickup_date_time" id="transferDateTimeInput" type="hidden">

                        <label class="field">
                            <span>Passengers</span>
                            <input name="passengers" type="number" min="1" max="16" value="2">
                        </label>
                        <label class="field">
                            <span>Pickup</span>
                            <input name="pickup_address" id="transferPickupInput" list="transferLocationOptions" placeholder="Hotel Princesa Yaiza" autocomplete="off">
                        </label>
                        <label class="field">
                            <span>Dropoff</span>
                            <input name="dropoff_address" id="transferDropoffInput" list="transferLocationOptions" placeholder="Hotel Beatriz Costa Teguise" autocomplete="off">
                        </label>

                        {{-- Mini-map --}}
                        <div id="transferMiniMapWrap" style="display:none;">
                            <div style="position:relative;border-radius:16px;overflow:hidden;border:1px solid rgba(21,19,15,.1);">
                                <div id="transferMiniMap" style="height:200px;width:100%;background:#dfe7dd;"></div>
                                <div id="transferRouteInfo" style="display:none;position:absolute;z-index:10000;bottom:0;left:0;right:0;background:rgba(255,255,255,.92);backdrop-filter:blur(8px);padding:10px 14px;align-items:center;justify-content:space-between;gap:10px;font-size:12px;">
                                    <div style="display:flex;align-items:center;gap:6px;"><span style="width:9px;height:9px;border-radius:50%;background:#087f5b;flex-shrink:0;display:inline-block;"></span><span id="transferPickupLabel" style="font-weight:700;color:var(--ink);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span></div>
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="var(--muted)" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                                    <div style="display:flex;align-items:center;gap:6px;"><span style="width:9px;height:9px;border-radius:50%;background:#dc4a25;flex-shrink:0;display:inline-block;"></span><span id="transferDropoffLabel" style="font-weight:700;color:var(--ink);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"></span></div>
                                    <span id="transferRouteMeta" style="color:var(--muted);font-weight:500;flex-shrink:0;"></span>
                                    <span id="transferPriceLabel" style="display:none;margin-left:auto;flex-shrink:0;background:var(--transfer);color:#fff;font-weight:800;font-size:13px;border-radius:8px;padding:3px 10px;"></span>
                                    <button id="transferConfirmBtn" type="button" style="display:none;flex-shrink:0;background:#087f5b;color:#fff;font-weight:700;font-size:12px;border-radius:8px;padding:5px 14px;border:none;cursor:pointer;white-space:nowrap;">Confirm &amp; pay</button>
                                </div>
                                <div id="transferMiniMapLoading" style="display:none;position:absolute;inset:0;background:rgba(255,255,255,.7);align-items:center;justify-content:center;backdrop-filter:blur(4px);">
                                    <div style="display:flex;align-items:center;gap:8px;color:#087f5b;font-weight:600;font-size:13px;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="animation:spin 1s linear infinite;"><path d="M21 12a9 9 0 11-6.219-8.56"/></svg>
                                        Calculating route…
                                    </div>
                                </div>
                            </div>
                        </div>

                        <input name="base_price" type="hidden">
                        <datalist id="transferLocationOptions"></datalist>
                    </div>
                </div>

                {{-- ── Shared contact fields (all types) ── --}}
                <div class="contact-fields" data-tour-step="contact">
                    <div class="request-step-body">
                        {{-- Tour: show step label --}}
                        <p class="step-label" data-tour-only style="display:none;">Step 3 of 3</p>
                        <p class="step-question">Your details</p>
                        <label class="field">
                            <span>Name</span>
                            <input name="customer_name" autocomplete="name" required>
                        </label>
                        <label class="field">
                            <span>Phone</span>
                            <input name="customer_phone" type="tel" autocomplete="tel">
                        </label>
                        <label class="field">
                            <span>Email</span>
                            <input name="customer_email" type="email" autocomplete="email">
                        </label>
                        <label class="field" data-taxi-route-pickup style="display:none;">
                            <span>Pickup address</span>
                            <input name="pickup_address" placeholder="Hotel, villa or pickup point">
                        </label>
                    </div>
                    {{-- Tour-only nav (back to step 2, forward to summary) --}}
                    <div class="step-nav" data-tour-nav style="display:none;">
                        <button class="step-btn-back" type="button" data-tour-back="calendar" aria-label="Back">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M19 12H5M12 5l-7 7 7 7"/></svg>
                        </button>
                        <button class="step-btn-next" style="--step-color:var(--tour)" type="button" data-tour-next="summary">
                            Review & Send
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                        </button>
                    </div>
                </div>

                {{-- ── Global footer: status + submit ── --}}
                <div class="request-footer">
                    <p id="requestStatus" class="request-status">Your details go only to the manager.</p>
                    <button id="requestSubmit" class="primary-action request-submit" type="submit">Send request</button>
                </div>

            </form>
        </div>
    </section>
<div x-data="{
    viewport: {
        lat: 40.7128,
        lng: -74.006,
        zoom: 8,
        bearing: 0,
        pitch: 0
    }
}" class="relative w-full h-[400px]">

    <x-map
        :center="[-74.006, 40.7128]"
        :zoom="8"
        @map:move="viewport.lat = $event.detail.lat; viewport.lng = $event.detail.lng"
        @map:zoom="viewport.zoom = $event.detail.zoom"
        @map:bearing-changed="viewport.bearing = $event.detail.bearing"
        @map:pitch-changed="viewport.pitch = $event.detail.pitch"
    />

    <div class="absolute top-2 left-2 z-10 flex gap-3 text-xs font-mono bg-white/90 backdrop-blur p-2 rounded border shadow-sm">
        <span><b class="text-gray-500">lng:</b> <span x-text="viewport.lng.toFixed(3)"></span></span>
        <span><b class="text-gray-500">lat:</b> <span x-text="viewport.lat.toFixed(3)"></span></span>
        <span><b class="text-gray-500">zoom:</b> <span x-text="viewport.zoom.toFixed(1)"></span></span>
        <span><b class="text-gray-500">bearing:</b> <span x-text="viewport.bearing.toFixed(1)"></span>°</span>
        <span><b class="text-gray-500">pitch:</b> <span x-text="viewport.pitch.toFixed(1)"></span>°</span>
    </div>
</div>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        const typeConfig = {
            hotel: {
                color: '#2563eb',
                fallback: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M5 21V7a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v14"/><path d="M9 21v-6h6v6"/><path d="M9 9h.01"/><path d="M15 9h.01"/><path d="M9 12h.01"/><path d="M15 12h.01"/>',
            },
            restaurant: {
                color: '#dc4a25',
                fallback: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M7 2v20"/><path d="M11 2v8a4 4 0 0 1-8 0V2"/><path d="M17 2v20"/><path d="M17 2c2.2 1.4 3.5 4.2 3.5 7.5S19.2 15.6 17 17"/>',
            },
            taxi: {
                color: '#141414',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M5 17h14l-1.4-5.4A3 3 0 0 0 14.7 9H9.3a3 3 0 0 0-2.9 2.6L5 17Z"/><path d="M7 17v2"/><path d="M17 17v2"/><path d="M9 9l1-3h4l1 3"/><path d="M7.5 14h.01"/><path d="M16.5 14h.01"/>',
            },
            tour_visit: {
                color: '#7c3aed',
                fallback: 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/>',
            },
            taxi_route: {
                color: '#0f766e',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M6 19V5"/><path d="M18 19V5"/><path d="M6 8h12"/><path d="M6 16h12"/><path d="M9 5a3 3 0 0 1 6 0"/><path d="M9 19a3 3 0 0 0 6 0"/>',
            },
            transfer: {
                color: '#0891b2',
                fallback: 'https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=900&q=80',
                icon: '<path d="M4 17h16"/><path d="M6 17V7a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10"/><path d="M8 17v2"/><path d="M16 17v2"/><path d="M9 9h6"/><path d="M9 13h6"/>',
            },
        };

        const state = {
            places: [],
            activeTypes: new Set(),
            search: '',
            markers: new Map(),
            selectedId: null,
            requestPlace: null,
        };

        const map = L.map('exploreMap', {
            zoomControl: false,
            scrollWheelZoom: true,
        }).setView([28.9249, -13.5098], 6);

        L.control.zoom({ position: 'bottomright' }).addTo(map);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(map);

        const layer = L.layerGroup().addTo(map);
        const resultsList = document.getElementById('resultsList');
        const resultsCount = document.getElementById('resultsCount');
        const detailDrawer = document.getElementById('detailDrawer');
        const mapTitle = document.getElementById('mapTitle');
        const mapSubtitle = document.getElementById('mapSubtitle');
        const requestModal = document.getElementById('requestModal');
        const requestForm = document.getElementById('requestForm');
        const requestStatus = document.getElementById('requestStatus');
        const requestSubmit = document.getElementById('requestSubmit');
        const tourSlotHint = document.getElementById('tourSlotHint');
        const tourSlotsPanel = document.getElementById('tourSlotsPanel');
        const tourSlotGrid = requestForm.querySelector('.tour-slot-grid');
        const tourDateInput = requestForm.querySelector('[name="tour_date"]');
        const tourScheduleInput = requestForm.querySelector('[name="tour_schedule"]');
        const tourCalendarNext = requestForm.querySelector('[data-tour-next="contact"]');
        const tourAdultsInput = requestForm.querySelector('[name="adults"]');
        const tourChildrenInput = requestForm.querySelector('[name="children"]');
        const tourAdultsValue = requestForm.querySelector('[data-stepper-value="adults"]');
        const tourChildrenSelect = requestForm.querySelector('[data-children-select]');
        const tourAttendeesSummary = document.getElementById('tourAttendeesSummary');
        let tourAvailabilityRequestId = 0;

        // ── Transfer mini-map ─────────────────────────────────────────────────
        let miniMap = null;
        let miniPickerMarker = null;
        let miniDropoffMarker = null;
        let miniRouteLayer = null;
        let miniMapInitialized = false;
        let transferRouteDebounce = null;

        const LANZAROTE_CENTER = [28.963, -13.648];

        function initMiniMap() {
            if (miniMapInitialized) { return; }
            miniMapInitialized = true;

            miniMap = L.map('transferMiniMap', {
                center: LANZAROTE_CENTER,
                zoom: 10,
                zoomControl: false,
                attributionControl: false,
                scrollWheelZoom: false,
            });

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
            }).addTo(miniMap);

            L.control.zoom({ position: 'topright' }).addTo(miniMap);
        }

        function showMiniMap() {
            const wrap = document.getElementById('transferMiniMapWrap');
            if (!wrap) { return; }
            wrap.style.display = '';
            initMiniMap();
            // Force Leaflet to recalculate size after display:none → visible
            setTimeout(() => miniMap?.invalidateSize(), 60);
        }

        function hideMiniMap() {
            const wrap = document.getElementById('transferMiniMapWrap');
            if (wrap) { wrap.style.display = 'none'; }
            clearMiniRouteElements();
        }

        function clearMiniRouteElements() {
            if (miniPickerMarker)  { miniMap?.removeLayer(miniPickerMarker);  miniPickerMarker  = null; }
            if (miniDropoffMarker) { miniMap?.removeLayer(miniDropoffMarker); miniDropoffMarker = null; }
            if (miniRouteLayer)    { miniMap?.removeLayer(miniRouteLayer);    miniRouteLayer    = null; }
            const info       = document.getElementById('transferRouteInfo');
            const loading    = document.getElementById('transferMiniMapLoading');
            const priceEl    = document.getElementById('transferPriceLabel');
            const confirmBtn = document.getElementById('transferConfirmBtn');
            if (info)       { info.style.display       = 'none'; }
            if (loading)    { loading.style.display    = 'none'; }
            if (priceEl)    { priceEl.style.display    = 'none'; priceEl.textContent = ''; }
            if (confirmBtn) { confirmBtn.style.display = 'none'; }
        }

        function makeMiniMarkerIcon(color) {
            return L.divIcon({
                className: '',
                html: `<div style="width:16px;height:16px;border-radius:50%;background:${color};border:2.5px solid white;box-shadow:0 2px 6px rgba(0,0,0,.35);"></div>`,
                iconSize: [16, 16],
                iconAnchor: [8, 8],
            });
        }

        async function updateMiniMapRoute() {
            const pickupVal  = document.getElementById('transferPickupInput')?.value?.trim();
            const dropoffVal = document.getElementById('transferDropoffInput')?.value?.trim();

            if (!pickupVal || !dropoffVal) { return; }

            showMiniMap();

            const loading = document.getElementById('transferMiniMapLoading');
            const info    = document.getElementById('transferRouteInfo');
            if (loading) { loading.style.display = 'flex'; }
            if (info)    { info.style.display    = 'none'; }

            clearMiniRouteElements();

            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const response  = await fetch('{{ route('maps.transfer-route') }}', {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ pickup: pickupVal, dropoff: dropoffVal, mode: 'drive' }),
                });

                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    // Geocoding not available — just show the map centred on Lanzarote
                    miniMap.setView(LANZAROTE_CENTER, 10);
                    if (loading) { loading.style.display = 'none'; }
                    return;
                }

                // Markers
                const pLat = data.pickup?.lat;
                const pLon = data.pickup?.lon;
                const dLat = data.dropoff?.lat;
                const dLon = data.dropoff?.lon;

                if (pLat && pLon) {
                    miniPickerMarker = L.marker([pLat, pLon], { icon: makeMiniMarkerIcon('#087f5b') })
                        .bindTooltip('Pickup', { direction: 'top', offset: [0, -10] })
                        .addTo(miniMap);
                }

                if (dLat && dLon) {
                    miniDropoffMarker = L.marker([dLat, dLon], { icon: makeMiniMarkerIcon('#dc4a25') })
                        .bindTooltip('Dropoff', { direction: 'top', offset: [0, -10] })
                        .addTo(miniMap);
                }

                // Route polyline
                if (data.route?.features?.length) {
                    miniRouteLayer = L.geoJSON(data.route, {
                        style: { color: '#0891b2', weight: 4, opacity: .85 },
                    }).addTo(miniMap);
                    miniMap.fitBounds(miniRouteLayer.getBounds(), { padding: [28, 28] });
                } else if (pLat && dLat) {
                    // Fallback: straight line
                    miniRouteLayer = L.polyline([[pLat, pLon], [dLat, dLon]], {
                        color: '#0891b2', weight: 3, dashArray: '6 5', opacity: .7,
                    }).addTo(miniMap);
                    miniMap.fitBounds([[pLat, pLon], [dLat, dLon]], { padding: [36, 36] });
                }

                // Route info bar
                const distance = data.meta?.distance ?? data.route?.features?.[0]?.properties?.distance;
                const duration = data.meta?.time     ?? data.route?.features?.[0]?.properties?.time;
                const distKm   = distance ? `${(distance / 1000).toFixed(1)} km` : '';
                const durMin   = duration ? `~${Math.round(duration / 60)} min` : '';

                document.getElementById('transferPickupLabel').textContent  = data.pickup?.formatted  || pickupVal;
                document.getElementById('transferDropoffLabel').textContent = data.dropoff?.formatted || dropoffVal;
                document.getElementById('transferRouteMeta').textContent    = [distKm, durMin].filter(Boolean).join(' · ');

                if (info) { info.style.display = 'flex'; }

                // Price estimate (non-blocking)
                const priceEl      = document.getElementById('transferPriceLabel');
                const confirmBtn   = document.getElementById('transferConfirmBtn');
                const passengersEl = requestForm.querySelector('[name="passengers"]');
                if (priceEl && pickupVal && dropoffVal) {
                    priceEl.style.display = 'none';
                    if (confirmBtn) { confirmBtn.style.display = 'none'; }
                    estimateTransferPrice({
                        pickup_address:  pickupVal,
                        dropoff_address: dropoffVal,
                        passengers:      passengersEl?.value || '1',
                    }).then(estimate => {
                        if (estimate?.estimated_price) {
                            priceEl.textContent   = `${Number(estimate.estimated_price).toFixed(2)} €`;
                            priceEl.style.display = 'inline';

                            if (confirmBtn) {
                                confirmBtn.style.display = 'inline-flex';
                                confirmBtn.onclick = () => {
                                    const basePriceInput = requestForm.querySelector('[name="base_price"]');
                                    if (basePriceInput) { basePriceInput.value = String(estimate.estimated_price); }
                                    requestSubmit.scrollIntoView({ behavior: 'smooth', block: 'center' });
                                    requestSubmit.click();
                                };
                            }
                        }
                    }).catch(() => { /* silently ignore if not available */ });
                }

            } catch (err) {
                console.error('Mini map route error:', err);
            } finally {
                if (loading) { loading.style.display = 'none'; }
            }
        }

        function scheduleTransferRouteUpdate() {
            clearTimeout(transferRouteDebounce);
            transferRouteDebounce = setTimeout(updateMiniMapRoute, 700);
        }

        // ── Tour + Transfer upsell wiring ────────────────────────────────────
        let upsellPriceDebounce = null;

        function populateTransferPickupTimes(tourTime) {
            const select = document.getElementById('tourTransferPickupTime');
            if (!select || !tourTime) { return; }
            const [hour, minute] = tourTime.split(':').map(Number);
            const tourMinutes = hour * 60 + minute;
            const options = [
                { label: '30 min before', offset: 30 },
                { label: '1 hour before', offset: 60 },
                { label: '1.5 hours before', offset: 90 },
                { label: '2 hours before', offset: 120 },
            ];
            select.innerHTML = '<option value="">Select pickup time</option>';
            options.forEach(opt => {
                const pickupMinutes = tourMinutes - opt.offset;
                if (pickupMinutes >= 0) {
                    const h = Math.floor(pickupMinutes / 60);
                    const m = pickupMinutes % 60;
                    const timeStr = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    select.innerHTML += `<option value="${timeStr}">${opt.label} (${timeStr})</option>`;
                }
            });
        }

        function refreshUpsellPrice() {
            const pickup     = document.getElementById('tourTransferPickup')?.value?.trim();
            const dropoff    = 'Bodega La Geria';
            const passengers = document.getElementById('tourTransferPassengers')?.value || '1';
            const info       = document.getElementById('tourTransferPriceInfo');
            if (!pickup || !info) { return; }
            info.textContent = 'Estimating transfer price…';
            estimateTransferPrice({ pickup_address: pickup, dropoff_address: dropoff, passengers })
                .then(est => {
                    if (est?.estimated_price) {
                        info.textContent = `Estimated transfer: ${Number(est.estimated_price).toFixed(2)} € · with 10% package discount`;
                    } else {
                        info.textContent = est?.error || 'Transfer price not available for this route.';
                    }
                })
                .catch(() => { info.textContent = ''; });
        }

        function scheduleUpsellPriceRefresh() {
            clearTimeout(upsellPriceDebounce);
            upsellPriceDebounce = setTimeout(refreshUpsellPrice, 700);
        }

        document.getElementById('addTransferToggle')?.addEventListener('change', (e) => {
            const fields = document.getElementById('tourTransferFields');
            if (fields) { fields.style.display = e.target.checked ? 'grid' : 'none'; }
            if (!e.target.checked) {
                document.getElementById('tourTransferPriceInfo').textContent = '';
            } else {
                // Preselect passengers from adults count
                const adultsInput = requestForm.querySelector('[name="adults"]');
                const passengersInput = document.getElementById('tourTransferPassengers');
                if (adultsInput && passengersInput) {
                    passengersInput.value = adultsInput.value || '1';
                }
                // Populate pickup times when tour time is already selected
                const tourSchedule = requestForm.querySelector('[name="tour_schedule"]')?.value;
                if (tourSchedule) { populateTransferPickupTimes(tourSchedule); }
            }
            updatePriceBreakdown();
        });

        document.addEventListener('input', (e) => {
            if (['tourTransferPickup', 'tourTransferPassengers'].includes(e.target.id)) {
                scheduleUpsellPriceRefresh();
            }
        });

        // When tour schedule changes, update pickup time options
        requestForm.addEventListener('change', (e) => {
            if (e.target.name === 'tour_schedule') {
                populateTransferPickupTimes(e.target.value);
            }
        });

        // Wire up transfer inputs via delegation (inputs exist in DOM already)
        requestForm.addEventListener('change', (e) => {
            if (e.target.id === 'transferPickupInput' || e.target.id === 'transferDropoffInput') {
                scheduleTransferRouteUpdate();
            }
        });
        // Also react on input (typing + selecting from datalist)
        requestForm.addEventListener('input', (e) => {
            if (e.target.id === 'transferPickupInput' || e.target.id === 'transferDropoffInput') {
                scheduleTransferRouteUpdate();
            }
        });

        function enableTransferMapMode()  { showMiniMap(); }
        function disableTransferMapMode() { hideMiniMap(); }

        // ── Transfer day/time picker ──────────────────────────────────────────
        let transferSelectedDate = null; // 'YYYY-MM-DD'
        let transferSelectedTime = null; // 'HH:MM'

        const TRANSFER_TIMES = (() => {
            const slots = [];
            for (let h = 5; h <= 23; h++) {
                slots.push(`${String(h).padStart(2, '0')}:00`);
                if (h < 23) { slots.push(`${String(h).padStart(2, '0')}:30`); }
            }
            return slots; // 05:00 … 23:00
        })();

        function initTransferDayGrid() {
            const weekday  = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
            const dayMonth = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });

            requestForm.querySelectorAll('[data-transfer-day]').forEach((btn, i) => {
                const d = new Date();
                d.setDate(d.getDate() + i);
                btn.dataset.transferDayValue = formatLocalDate(d);
                btn.querySelectorAll('span')[0].textContent = i === 0 ? 'Today' : i === 1 ? 'Tomorrow' : weekday.format(d);
                btn.querySelectorAll('span')[1].textContent = dayMonth.format(d);
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Build time grid
            const grid = document.getElementById('transferTimeGrid');
            if (grid && !grid.children.length) {
                grid.innerHTML = TRANSFER_TIMES.map(t =>
                    `<button class="transfer-time" data-transfer-time="${t}" type="button" aria-pressed="false">${t}</button>`
                ).join('');
                grid.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-transfer-time]');
                    if (btn) { selectTransferTime(btn); }
                });
            }
        }

        function selectTransferDay(btn) {
            requestForm.querySelectorAll('[data-transfer-day]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            transferSelectedDate = btn.dataset.transferDayValue;
            transferSelectedTime = null;

            // Reset time selection
            requestForm.querySelectorAll('[data-transfer-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            document.getElementById('transferTimePanel')?.classList.add('open');
            updateTransferDateTimeInput();
        }

        function selectTransferTime(btn) {
            requestForm.querySelectorAll('[data-transfer-time]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            transferSelectedTime = btn.dataset.transferTime;
            updateTransferDateTimeInput();

            // Scroll to make the active time visible
            btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function updateTransferDateTimeInput() {
            const input   = document.getElementById('transferDateTimeInput');
            const summary = document.getElementById('transferDateSummary');
            const sumText = document.getElementById('transferDateSummaryText');

            if (transferSelectedDate && transferSelectedTime) {
                const isoValue = `${transferSelectedDate}T${transferSelectedTime}`;
                if (input) { input.value = isoValue; }

                // Format for display
                const d      = new Date(isoValue);
                const label  = new Intl.DateTimeFormat(undefined, {
                    weekday: 'short', day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit',
                }).format(d);

                if (sumText)  { sumText.textContent = label; }
                if (summary)  { summary.classList.add('visible'); }

                // Collapse day + time grids, show summary
                document.getElementById('transferDayGrid').style.display  = 'none';
                document.getElementById('transferTimePanel').classList.remove('open');
            } else {
                if (input)   { input.value = ''; }
                if (summary) { summary.classList.remove('visible'); }
            }
        }

        function resetTransferDatePicker() {
            transferSelectedDate = null;
            transferSelectedTime = null;
            const input   = document.getElementById('transferDateTimeInput');
            const summary = document.getElementById('transferDateSummary');
            const panel   = document.getElementById('transferTimePanel');
            const grid    = document.getElementById('transferDayGrid');
            if (input)   { input.value = ''; }
            if (summary) { summary.classList.remove('visible'); }
            if (panel)   { panel.classList.remove('open'); }
            if (grid)    { grid.style.display = ''; }
            requestForm.querySelectorAll('[data-transfer-day],[data-transfer-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
        }

        // ── Restaurant day/time picker ──────────────────────────────────────────
        let restaurantSelectedDate = null; // 'YYYY-MM-DD'
        let restaurantSelectedTime = null; // 'HH:MM'

        const RESTAURANT_TIMES = (() => {
            const slots = [];
            for (let h = 12; h <= 23; h++) {
                slots.push(`${String(h).padStart(2, '0')}:00`);
                if (h < 23) { slots.push(`${String(h).padStart(2, '0')}:30`); }
            }
            return slots; // 12:00 … 23:00
        })();

        function initRestaurantDayGrid() {
            const weekday  = new Intl.DateTimeFormat(undefined, { weekday: 'short' });
            const dayMonth = new Intl.DateTimeFormat(undefined, { day: 'numeric', month: 'short' });

            requestForm.querySelectorAll('[data-restaurant-day]').forEach((btn, i) => {
                const d = new Date();
                d.setDate(d.getDate() + i);
                btn.dataset.restaurantDayValue = formatLocalDate(d);
                btn.querySelectorAll('span')[0].textContent = i === 0 ? 'Today' : i === 1 ? 'Tomorrow' : weekday.format(d);
                btn.querySelectorAll('span')[1].textContent = dayMonth.format(d);
                btn.classList.remove('active');
                btn.setAttribute('aria-pressed', 'false');
            });

            // Build time grid
            const grid = document.getElementById('restaurantTimeGrid');
            if (grid && !grid.children.length) {
                grid.innerHTML = RESTAURANT_TIMES.map(t =>
                    `<button class="slot-chip" data-restaurant-time="${t}" type="button" aria-pressed="false">${t}</button>`
                ).join('');
                grid.addEventListener('click', (e) => {
                    const btn = e.target.closest('[data-restaurant-time]');
                    if (btn) { selectRestaurantTime(btn); }
                });
            }
        }

        function selectRestaurantDay(btn) {
            requestForm.querySelectorAll('[data-restaurant-day]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            restaurantSelectedDate = btn.dataset.restaurantDayValue;
            restaurantSelectedTime = null;

            // Reset time selection
            requestForm.querySelectorAll('[data-restaurant-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });

            document.getElementById('restaurantTimePanel')?.classList.add('open');
            updateRestaurantDateTimeInput();
        }

        function selectRestaurantTime(btn) {
            requestForm.querySelectorAll('[data-restaurant-time]').forEach(b => {
                b.classList.toggle('active', b === btn);
                b.setAttribute('aria-pressed', b === btn ? 'true' : 'false');
            });

            restaurantSelectedTime = btn.dataset.restaurantTime;
            updateRestaurantDateTimeInput();

            // Sync Alpine state so restTime reacts
            const restEl = requestForm.querySelector('[data-request-fields="restaurant"] [x-data]');
            if (restEl && window.Alpine) { Alpine.$data(restEl).restTime = restaurantSelectedTime; }

            btn.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
        }

        function updateRestaurantDateTimeInput() {
            const dateInput = document.getElementById('restaurantDateInput');
            const timeInput = document.getElementById('restaurantTimeInput');

            if (restaurantSelectedDate && restaurantSelectedTime) {
                if (dateInput) { dateInput.value = restaurantSelectedDate; }
                if (timeInput) { timeInput.value = restaurantSelectedTime; }
            } else {
                if (dateInput) { dateInput.value = ''; }
                if (timeInput) { timeInput.value = ''; }
            }
        }

        function resetRestaurantDatePicker() {
            restaurantSelectedDate = null;
            restaurantSelectedTime = null;
            const dateInput = document.getElementById('restaurantDateInput');
            const timeInput = document.getElementById('restaurantTimeInput');
            if (dateInput) { dateInput.value = ''; }
            if (timeInput) { timeInput.value = ''; }
            requestForm.querySelectorAll('[data-restaurant-day],[data-restaurant-time]').forEach(b => {
                b.classList.remove('active');
                b.setAttribute('aria-pressed', 'false');
            });
            // Reset Alpine state
            const restEl = requestForm.querySelector('[data-request-fields="restaurant"] [x-data]');
            if (restEl && window.Alpine) {
                const d = Alpine.$data(restEl);
                d.restDate = null; d.restTime = null; d.restStep = 1;
            }
        }

        // ── Restaurant guest selector ───────────────────────────────────────────
        requestForm.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-guest-action]');
            if (!btn) return;

            const action = btn.dataset.guestAction;
            const input  = requestForm.querySelector('[name="guests"]');
            if (!input) return;

            let value = parseInt(input.value, 10) || 2;
            const min = parseInt(input.min, 10) || 1;
            const max = parseInt(input.max, 10) || 30;

            if (action === 'minus' && value > min) {
                value--;
            } else if (action === 'plus' && value < max) {
                value++;
            }

            input.value = value;
        });

        // Summary chip click → reset to show day grid again
        document.getElementById('transferDateSummary')?.addEventListener('click', resetTransferDatePicker);
        document.getElementById('transferDateSummary')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { resetTransferDatePicker(); }
        });

        document.getElementById('restaurantDateSummary')?.addEventListener('click', resetRestaurantDatePicker);
        document.getElementById('restaurantDateSummary')?.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') { resetRestaurantDatePicker(); }
        });

        // Day buttons click delegation
        requestForm.addEventListener('click', (e) => {
            const dayBtn = e.target.closest('[data-transfer-day]');
            if (dayBtn) { selectTransferDay(dayBtn); }

            const restaurantDayBtn = e.target.closest('[data-restaurant-day]');
            if (restaurantDayBtn) { selectRestaurantDay(restaurantDayBtn); }
        });

        fetch('{{ route('public.explore.places') }}')
            .then(response => response.json())
            .then(payload => {
                state.places = payload.data || [];
                updateCounts(payload.meta?.types || {});
                hydrateTransferOptions();
                render();
                fitMap();
            })
            .catch(() => {
                resultsList.innerHTML = '<div class="empty-state">We could not load the public map data right now. Please try again in a moment.</div>';
                resultsCount.textContent = 'Unavailable';
            });

        document.querySelectorAll('.filter-btn').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.filter;
                const checked = !state.activeTypes.has(type);

                if (checked) {
                    state.activeTypes.add(type);
                } else {
                    state.activeTypes.delete(type);
                }

                updateFilterState(button, checked);
                state.selectedId = null;
                render();
            });
        });

        document.getElementById('searchInput').addEventListener('input', event => {
            state.search = event.target.value.trim().toLowerCase();
            render();
        });

        document.getElementById('fitMapBtn').addEventListener('click', fitMap);
        document.getElementById('closeRequestModal').addEventListener('click', closeRequestModal);
        requestModal.addEventListener('click', event => {
            if (event.target === requestModal) {
                closeRequestModal();
            }
        });
        requestForm.addEventListener('submit', submitRequest);
        requestForm.querySelectorAll('[data-tour-next]').forEach(button => {
            button.addEventListener('click', async () => {
                const next = button.dataset.tourNext;
                if (next !== 'contact') {
                    setTourStep(next);
                    return;
                }

                const ok = await confirmTourSelectionAvailability();
                if (ok) {
                    setTourStep('contact');
                }
            });
        });
        requestForm.querySelectorAll('[data-tour-back]').forEach(button => {
            button.addEventListener('click', () => setTourStep(button.dataset.tourBack));
        });
        requestForm.querySelectorAll('[data-tour-day]').forEach(button => {
            button.addEventListener('click', () => selectTourDay(button));
        });
        tourSlotGrid?.addEventListener('click', event => {
            const button = event.target.closest('[data-tour-slot]');
            if (!button || button.disabled) {
                return;
            }

            selectTourSlot(button);
        });

        requestForm.querySelectorAll('[data-stepper-plus],[data-stepper-minus]').forEach(button => {
            button.addEventListener('click', () => handleStepper(button));
        });
        tourChildrenSelect?.addEventListener('change', () => {
            tourChildrenInput.value = String(parseInt(tourChildrenSelect.value || '0', 10) || 0);
            updateAttendeesSummary();
            handleParticipantsChanged();
        });

        document.getElementById('clearSelectionBtn').addEventListener('click', () => {
            state.selectedId = null;
            detailDrawer.classList.remove('open');
            renderCards(filteredPlaces());
            refreshMarkerStates();
        });

        document.getElementById('nearMeBtn').addEventListener('click', () => {
            if (!navigator.geolocation) {
                mapSubtitle.textContent = 'Your browser does not support location detection.';
                return;
            }

            navigator.geolocation.getCurrentPosition(position => {
                const { latitude, longitude } = position.coords;
                L.circleMarker([latitude, longitude], {
                    radius: 8,
                    color: '#087f5b',
                    weight: 3,
                    fillColor: '#087f5b',
                    fillOpacity: .22,
                }).addTo(map);
                map.setView([latitude, longitude], 14, { animate: true });
                mapSubtitle.textContent = 'Centered on your location. Choose a marker nearby.';
            }, () => {
                mapSubtitle.textContent = 'Location permission was not available.';
            });
        });

        function render() {
            const places = filteredPlaces();
            const mappedPlaces = mappablePlaces(places);

            renderMarkers(mappedPlaces);
            renderCards(places);
            updateSummary(places, mappedPlaces);
        }

        function hydrateTransferOptions() {
            const datalist = document.getElementById('transferLocationOptions');
            if (!datalist) {
                return;
            }

            const names = Array.from(new Set(state.places
                .filter(place => ['hotel', 'restaurant'].includes(place.type))
                .map(place => place.name)
                .filter(Boolean)))
                .sort((a, b) => a.localeCompare(b));

            datalist.innerHTML = names.map(name => `<option value="${escapeHtml(name)}"></option>`).join('');
        }

        function filteredPlaces() {
            return state.places.filter(place => {
                const matchesType = state.activeTypes.has(place.type);
                const haystack = [place.name, place.label, place.address, place.description, ...Object.values(place.summary || {})]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                return matchesType && (!state.search || haystack.includes(state.search));
            });
        }

        function mappablePlaces(places) {
            return places.filter(place => place.has_coordinates && place.latitude !== null && place.longitude !== null);
        }

        function updateFilterState(button, checked) {
            button.classList.toggle('active', checked);
            button.setAttribute('aria-checked', checked ? 'true' : 'false');
        }

        function renderMarkers(places) {
            layer.clearLayers();
            state.markers.clear();

            places.forEach(place => {
                const marker = L.marker([place.latitude, place.longitude], {
                    icon: markerIcon(place),
                    riseOnHover: true,
                }).addTo(layer);

                marker.bindPopup(popupHtml(place), { closeButton: false, offset: [0, -10] });
                marker.on('click', () => selectPlace(place.id, true));
                state.markers.set(place.id, marker);
            });

            refreshMarkerStates();
        }

        function renderCards(places) {
            resultsCount.textContent = `${places.length} result${places.length === 1 ? '' : 's'}`;

            if (!places.length) {
                const msg = state.activeTypes.size === 0
                    ? 'Select at least one category above to explore places.'
                    : 'No places match the current filters. Try another category or search term.';
                resultsList.innerHTML = `<div class="empty-state">${msg}</div>`;
                return;
            }

            resultsList.innerHTML = places.map((place, index) => {
                const mapBadge = place.has_coordinates
                    ? '<span class="map-badge on-map">On map</span>'
                    : '<span class="map-badge">List only</span>';

                return `
                <div class="place-card  ${state.selectedId === place.id ? 'selected' : ''} ${place.has_coordinates ? '' : 'unmapped'}" style="--place-color: ${typeConfig[place.type].color}; animation-delay: ${Math.min(index * 32, 240)}ms" type="button"  >
                    <img src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                    <span class="place-body">
                        <span class="place-meta">
                            <span class="type-pill">${escapeHtml(place.label)}</span>
                            <span style="display: flex; align-items: center; gap: 6px;">
                                ${mapBadge}
                                <span class="rating">${place.rating ? `★ ${place.rating}` : 'New'}</span>
                            </span>
                        </span>
                        <h2>${escapeHtml(place.name)}</h2>
                        <p>${escapeHtml(place.address || place.description || 'Public destination')}</p>
                        <span class="mini-facts">
                            ${Object.entries(place.summary || {}).slice(0, 3).map(([key, value]) => `<span>${escapeHtml(key)}: ${escapeHtml(value)}</span>`).join('')}
                        <a class="open-btn request-open"  data-place-id="${escapeHtml(place.id)}" data-request-place="${escapeHtml(place.id)}">Reservar</a>
<a class="open-btn place-card"  data-request-place="${escapeHtml(place.id)}" data-place-id="${escapeHtml(place.id)}">Info</a>


    </span>
                    </span>
                </div>
            `;
            }).join('');

            resultsList.querySelectorAll('.place-card').forEach(card => {
                card.addEventListener('click', () => selectPlace(card.dataset.placeId, true));
            });

            resultsList.querySelectorAll('.request-open').forEach(card => {
                card.addEventListener('click', () => openRequestModal(card.dataset.placeId, true));
            });

        }
        function selectPlace(id, moveMap = false) {
            const place = state.places.find(item => item.id === id);
            if (!place) {
                return;
            }

            state.selectedId = id;
            renderCards(filteredPlaces());
            renderDetail(place);
            refreshMarkerStates();

            const marker = state.markers.get(id);
            if (marker) {
                marker.openPopup();
            }

            if (moveMap && place.has_coordinates) {
                map.flyTo([place.latitude, place.longitude], Math.max(map.getZoom(), 14), {
                    animate: true,
                    duration: .85,
                });
            } else if (moveMap) {
                mapSubtitle.textContent = `${place.name} is listed but does not have map coordinates yet.`;
            }
        }

        function renderDetail(place) {
            detailDrawer.innerHTML = `
                <img class="detail-image" src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                <div class="detail-content" style="--place-color: ${typeConfig[place.type].color}">
                    <span class="type-pill">${escapeHtml(place.label)}</span>
                    <h3>${escapeHtml(place.name)}</h3>
                    <p>${escapeHtml(place.description || place.address || 'Explore this public service on the map.')}</p>
                    <div class="mini-facts" style="margin-bottom: 14px">
                        ${Object.entries(place.summary || {}).slice(0, 3).map(([key, value]) => `<span>${escapeHtml(key)}: ${escapeHtml(value)}</span>`).join('')}
                    </div>
                    <div class="detail-actions">
                        <button class="primary-action request-open" type="button" data-request-place="${escapeHtml(place.id)}">Request booking</button>
                        ${place.phone ? `<a class="secondary-action" href="tel:${escapeHtml(place.phone)}">Call now</a>` : ''}
                        ${place.website ? `<a class="secondary-action" href="${escapeHtml(place.website)}" target="_blank" rel="noopener">Website</a>` : ''}
                        ${place.has_coordinates ? `<a class="secondary-action" href="https://www.google.com/maps/search/?api=1&query=${place.latitude},${place.longitude}" target="_blank" rel="noopener">Directions</a>` : '<span class="secondary-action">No map location yet</span>'}
                    </div>
                </div>
            `;
            detailDrawer.classList.add('open');
            detailDrawer.querySelector('.request-open')?.addEventListener('click', () => openRequestModal2(place));
        }
        function openRequestModal(id) {
                        const place = state.places.find(item => item.id === id);
            if (!place) {
                return;
            }

            state.requestPlace = place;
            const requestType = bookingType(place);
            requestForm.reset();
            clearMiniRouteElements();
            hideMiniMap();
            resetTransferDatePicker();
            requestForm.classList.toggle('tour-mode', requestType === 'tour');
            requestForm.dataset.tourCurrentStep = requestType === 'tour' ? 'calendar' : '';
            requestForm.dataset.step = requestType === 'tour' ? '' : 'fields';
            resetTourWizard();
            requestStatus.className = 'request-status';
            requestStatus.textContent = 'Your details go only to the manager.';
            requestSubmit.disabled = false;
            requestSubmit.textContent = 'Send request';

            document.getElementById('requestType').textContent = place.label;
            document.getElementById('requestType').style.color = typeConfig[place.type].color;
            document.getElementById('requestTitle').textContent = place.name;
            const subtitleEl = document.getElementById('requestSubtitle');
            if (subtitleEl) {
                subtitleEl.textContent = `${place.address || place.label}.` + (place.source_label ? ` ${place.source_label}.` : '');
            }
            // Colour the submit button to match service type
            requestSubmit.style.background = typeConfig[place.type].color;

            document.querySelectorAll('[data-request-fields]').forEach(group => {
                const active = group.dataset.requestFields === requestType;
                group.classList.toggle('active', active);
                group.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = !active;
                    if (active) {
                        input.required = requiredTypeFields(requestType).includes(input.name);
                    }
                });
            });
            requestForm.querySelectorAll('[data-taxi-route-pickup]').forEach(group => {
                const active = place.type === 'taxi_route';
                group.style.display = active ? '' : 'none';
                group.querySelectorAll('input').forEach(input => {
                    input.disabled = !active;
                    input.required = active;
                });
            });

            // Enable transfer map mode + init day picker
            if (place.type === 'transfer') {
                initTransferDayGrid();
                enableTransferMapMode();
            } else {
                disableTransferMapMode();
            }

            // Init restaurant day picker
            if (place.type === 'restaurant') {
                initRestaurantDayGrid();
            }

            // Show transfer upsell for tour_visit, restaurant, and hotel
            const upsell = document.getElementById('tourTransferUpsell');
            const addTransferToggle = document.getElementById('addTransferToggle');
            const dropoffInput = document.getElementById('tourTransferDropoff');
            if (upsell) {
                const showUpsell = ['tour_visit', 'restaurant', 'hotel'].includes(place.type);
                upsell.style.display = showUpsell ? '' : 'none';
                if (addTransferToggle) { addTransferToggle.checked = false; }
                document.getElementById('tourTransferFields').style.display = 'none';
                document.getElementById('tourTransferPriceInfo').textContent = '';

                // Set dropoff based on place type
                if (dropoffInput) {
                    if (place.type === 'tour_visit') {
                        dropoffInput.value = 'Bodega La Geria';
                    } else if (place.type === 'restaurant') {
                        dropoffInput.value = place.address || place.label || '';
                    } else if (place.type === 'hotel') {
                        dropoffInput.value = place.address || place.label || '';
                    } else {
                        dropoffInput.value = '';
                    }
                }
            }

            requestModal.classList.add('open');
            requestModal.setAttribute('aria-hidden', 'false');

            // Re-init Alpine on the panel so BlatUI components that were in display:none get processed
            const panel = document.querySelector('.request-panel');
            if (panel && window.Alpine) {
                Alpine.initTree(panel);
            }

            if (requestType !== 'tour') {
                setTimeout(() => requestForm.querySelector('.type-fields.active input:not([type=hidden])')?.focus(), 80);
            }
        }

        function openRequestModal2(place) {
  
            state.requestPlace = place;
            const requestType = bookingType(place);
            requestForm.reset();
            clearMiniRouteElements();
            hideMiniMap();
            resetTransferDatePicker();
            requestForm.classList.toggle('tour-mode', requestType === 'tour');
            requestForm.dataset.tourCurrentStep = requestType === 'tour' ? 'calendar' : '';
            requestForm.dataset.step = requestType === 'tour' ? '' : 'fields';
            resetTourWizard();
            requestStatus.className = 'request-status';
            requestStatus.textContent = 'Your details go only to the manager.';
            requestSubmit.disabled = false;
            requestSubmit.textContent = 'Send request';

            document.getElementById('requestType').textContent = place.label;
            document.getElementById('requestType').style.color = typeConfig[place.type].color;
            document.getElementById('requestTitle').textContent = place.name;
            const subtitleEl = document.getElementById('requestSubtitle');
            if (subtitleEl) {
                subtitleEl.textContent = `${place.address || place.label}.` + (place.source_label ? ` ${place.source_label}.` : '');
            }
            // Colour the submit button to match service type
            requestSubmit.style.background = typeConfig[place.type].color;

            document.querySelectorAll('[data-request-fields]').forEach(group => {
                const active = group.dataset.requestFields === requestType;
                group.classList.toggle('active', active);
                group.querySelectorAll('input, textarea, select').forEach(input => {
                    input.disabled = !active;
                    if (active) {
                        input.required = requiredTypeFields(requestType).includes(input.name);
                    }
                });
            });
            requestForm.querySelectorAll('[data-taxi-route-pickup]').forEach(group => {
                const active = place.type === 'taxi_route';
                group.style.display = active ? '' : 'none';
                group.querySelectorAll('input').forEach(input => {
                    input.disabled = !active;
                    input.required = active;
                });
            });

            // Enable transfer map mode + init day picker
            if (place.type === 'transfer') {
                initTransferDayGrid();
                enableTransferMapMode();
            } else {
                disableTransferMapMode();
            }

            // Init restaurant day picker
            if (place.type === 'restaurant') {
                initRestaurantDayGrid();
            }

            // Show transfer upsell for tour_visit, restaurant, and hotel
            const upsell = document.getElementById('tourTransferUpsell');
            const addTransferToggle = document.getElementById('addTransferToggle');
            const dropoffInput = document.getElementById('tourTransferDropoff');
            if (upsell) {
                const showUpsell = ['tour_visit', 'restaurant', 'hotel'].includes(place.type);
                upsell.style.display = showUpsell ? '' : 'none';
                if (addTransferToggle) { addTransferToggle.checked = false; }
                document.getElementById('tourTransferFields').style.display = 'none';
                document.getElementById('tourTransferPriceInfo').textContent = '';

                // Set dropoff based on place type
                if (dropoffInput) {
                    if (place.type === 'tour_visit') {
                        dropoffInput.value = 'Bodega La Geria';
                    } else if (place.type === 'restaurant') {
                        dropoffInput.value = place.address || place.label || '';
                    } else if (place.type === 'hotel') {
                        dropoffInput.value = place.address || place.label || '';
                    } else {
                        dropoffInput.value = '';
                    }
                }
            }

            requestModal.classList.add('open');
            requestModal.setAttribute('aria-hidden', 'false');

            // Re-init Alpine on the panel so BlatUI components that were in display:none get processed
            const panel = document.querySelector('.request-panel');
            if (panel && window.Alpine) {
                Alpine.initTree(panel);
            }

            if (requestType !== 'tour') {
                setTimeout(() => requestForm.querySelector('.type-fields.active input:not([type=hidden])')?.focus(), 80);
            }
        }

        function closeRequestModal() {
            requestModal.classList.remove('open');
            requestModal.setAttribute('aria-hidden', 'true');
            state.requestPlace = null;
            disableTransferMapMode();
        }

        function resetTourWizard() {
            if (tourSlotGrid) {
                tourSlotGrid.innerHTML = '';
            }

            if (tourDateInput) {
                tourDateInput.value = '';
            }

            if (tourScheduleInput) {
                tourScheduleInput.value = '';
            }

            tourSlotsPanel?.classList.remove('open');
            if (tourSlotHint) {
                tourSlotHint.textContent = 'Select a day to see available slots.';
            }

            // Reset Alpine stepper state if the component is initialised (Alpine v3)
            const tourEl = requestForm.querySelector('[data-request-fields="tour"] [data-slot="stepper"]');
            if (tourEl && window.Alpine) {
                Alpine.$data(tourEl)?.resetWizard?.();
            }

            syncTourSlots();
        }

        // Expose functions globally so Alpine can call them
        window.setTourStep = setTourStep;
        window.confirmTourSelectionAvailability = confirmTourSelectionAvailability;

        function formatLocalDate(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        }

        function setTourStep(step) {
            requestForm.dataset.tourCurrentStep = step;

            if (step === 'attendees') {
                updateAttendeesSummary();
            }

            if (step === 'contact') {
                requestForm.querySelector('[name="customer_name"]')?.focus();
            }

            if (step === 'summary') {
                requestForm.querySelector('[name="notes"]')?.focus();
                updatePriceBreakdown();
            }
        }

        function updatePriceBreakdown() {
            const breakdown = document.getElementById('priceBreakdown');
            const serviceSubtotalEl = document.getElementById('serviceSubtotal');
            const transferPriceRow = document.getElementById('transferPriceRow');
            const transferPriceDisplay = document.getElementById('transferPriceDisplay');
            const discountRow = document.getElementById('discountRow');
            const discountDisplay = document.getElementById('discountDisplay');
            const totalDisplay = document.getElementById('totalPriceDisplay');

            if (!breakdown || !state.requestPlace) { return; }

            breakdown.style.display = 'grid';

            // Calculate service subtotal
            let serviceSubtotal = 0;
            const adults = parseInt(requestForm.querySelector('[name="adults"]')?.value || '1', 10);
            const unitPrice = state.requestPlace.unit_price || 0;
            serviceSubtotal = unitPrice * adults;

            serviceSubtotalEl.textContent = `${serviceSubtotal.toFixed(2)} €`;

            // Transfer price
            const addTransfer = document.getElementById('addTransferToggle')?.checked;
            let transferPrice = 0;
            if (addTransfer) {
                const priceInfo = document.getElementById('tourTransferPriceInfo')?.textContent;
                const match = priceInfo?.match(/Estimated transfer:\s*([\d.]+)/);
                if (match) {
                    transferPrice = parseFloat(match[1]) || 0;
                }
            }

            if (addTransfer && transferPrice > 0) {
                transferPriceRow.style.display = 'flex';
                transferPriceDisplay.textContent = `${transferPrice.toFixed(2)} €`;
            } else {
                transferPriceRow.style.display = 'none';
            }

            // Discount and total
            let total = serviceSubtotal + transferPrice;
            if (addTransfer) {
                const discount = total * 0.10;
                discountRow.style.display = 'flex';
                discountDisplay.textContent = `-${discount.toFixed(2)} €`;
                total -= discount;
            } else {
                discountRow.style.display = 'none';
            }

            totalDisplay.textContent = `${total.toFixed(2)} €`;
        }

        function selectTourDay(button) {
            requestForm.querySelectorAll('[data-tour-day]').forEach(dayButton => {
                dayButton.classList.toggle('active', dayButton === button);
                dayButton.setAttribute('aria-pressed', dayButton === button ? 'true' : 'false');
            });

            tourDateInput.value = button.dataset.tourDayValue;
            tourScheduleInput.value = '';

            if (tourSlotGrid) {
                tourSlotGrid.innerHTML = '';
            }

            tourSlotsPanel?.classList.add('open');
            if (tourSlotHint) {
                tourSlotHint.textContent = 'Loading slots...';
            }
            loadTourAvailability(button.dataset.tourDayValue);
            syncTourSlots();
        }

        function selectTourSlot(button) {
            tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                slotButton.classList.toggle('active', slotButton === button);
                slotButton.setAttribute('aria-pressed', slotButton === button ? 'true' : 'false');
            });

            tourScheduleInput.value = button.dataset.tourSlot;

            // Sync Alpine stepper state so canProceedToAttendees getter reacts
            const tourEl = requestForm.querySelector('[data-slot="stepper"]');
            if (tourEl && window.Alpine) {
                Alpine.$data(tourEl).tourSchedule = button.dataset.tourSlot;
            }

            syncTourSlots();
        }

        // Expose slot loaders globally for Alpine calendar-change listeners
        window.loadTourSlots = function(dateValue) { loadTourAvailability(dateValue); };

        function loadTourAvailability(dateValue) {
            if (!state.requestPlace || !tourSlotGrid) {
                return;
            }

            const requestId = ++tourAvailabilityRequestId;
            const url = new URL('{{ route('public.explore.availability') }}', window.location.origin);
            url.searchParams.set('type', state.requestPlace.type);
            url.searchParams.set('service_id', state.requestPlace.model_id);
            url.searchParams.set('date', dateValue);
            url.searchParams.set('participants', String(currentParticipants()));

            fetch(url.toString(), { headers: { 'Accept': 'application/json' } })
                .then(async response => {
                    const body = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = Object.values(body.errors || {})[0]?.[0];
                        throw new Error(firstError || body.message || 'Availability could not be loaded.');
                    }

                    return body;
                })
                .then(body => {
                    if (requestId !== tourAvailabilityRequestId) {
                        return;
                    }

                    const slots = body.data?.times || [];
                    const sourceLabel = body.data?.source?.source_label;

                    tourSlotGrid.innerHTML = '';

                    if (!Array.isArray(slots) || slots.length === 0) {
                        tourSlotHint.textContent = 'No slots available for the selected day.';
                        return;
                    }

                    tourSlotHint.textContent = sourceLabel ? `Slots from ${sourceLabel}.` : 'Available slots for the selected day.';
            const info    = document.getElementById('tourSlotsPanel');
            info.style.display    = 'block'; 

                    slots.forEach(slot => {
                        const button = document.createElement('button');
                        button.type = 'button';
                        button.className = 'slot-chip tour-slot';
                        button.dataset.tourSlot = slot.time;
                        button.textContent = slot.time;
                        button.disabled = slot.available === false;
                        button.setAttribute('aria-pressed', 'false');
                        tourSlotGrid.appendChild(button);
                    });
                })
                .catch(error => {
                    if (requestId !== tourAvailabilityRequestId) {
                        return;
                    }

                    tourSlotGrid.innerHTML = '';
                    tourSlotHint.textContent = error.message;
                });
        }

        function currentParticipants() {
            const adults = parseInt(tourAdultsInput?.value || '1', 10);
            const safeAdults = Number.isFinite(adults) && adults > 0 ? adults : 1;
            return safeAdults;
        }

        async function confirmTourSelectionAvailability() {
            if (!state.requestPlace || !tourDateInput?.value || !tourScheduleInput?.value) {
                return false;
            }

            if (tourSlotHint) {
                tourSlotHint.textContent = 'Checking availability...';
            }

            const url = new URL('{{ route('public.explore.availability') }}', window.location.origin);
            url.searchParams.set('type', state.requestPlace.type);
            url.searchParams.set('service_id', state.requestPlace.model_id);
            url.searchParams.set('date', tourDateInput.value);
            url.searchParams.set('participants', String(currentParticipants()));

            try {
                const response = await fetch(url.toString(), { headers: { 'Accept': 'application/json' } });
                const body = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const firstError = Object.values(body.errors || {})[0]?.[0];
                    throw new Error(firstError || body.message || 'Availability could not be checked.');
                }

                const slots = body.data?.times || [];
                const selected = slots.find(slot => slot.time === tourScheduleInput.value);

                if (!selected || selected.available === false) {
                    tourScheduleInput.value = '';
                    tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                        slotButton.classList.toggle('active', false);
                        slotButton.setAttribute('aria-pressed', 'false');
                    });
                    syncTourSlots();

                    if (tourSlotHint) {
                        tourSlotHint.textContent = 'That slot is no longer available. Please choose another time.';
                    }

                    return false;
                }

                if (tourSlotHint) {
                    tourSlotHint.textContent = 'Slot confirmed. Continue to add contact details.';
                }

                return true;
            } catch (error) {
                if (tourSlotHint) {
                    tourSlotHint.textContent = error.message || 'Availability could not be checked.';
                }
                return false;
            }
        }

        function syncTourSlots() {
            if (tourCalendarNext) {
                tourCalendarNext.disabled = !(tourDateInput?.value && tourScheduleInput?.value);
            }
        }

        function updateAttendeesSummary() {
            if (!tourAttendeesSummary) {
                return;
            }

            const adults = parseInt(tourAdultsInput?.value || '1', 10) || 1;
            const children = parseInt(tourChildrenInput?.value || '0', 10) || 0;
            tourAttendeesSummary.textContent = children > 0
                ? `Attendees: ${adults} · Children (free): ${children}`
                : `Attendees: ${adults}`;
        }

        function handleStepper(button) {
            const key = button.dataset.stepperPlus || button.dataset.stepperMinus;
            if (key !== 'adults') {
                return;
            }

            const current = parseInt(tourAdultsInput?.value || '1', 10) || 1;
            const delta = button.hasAttribute('data-stepper-plus') ? 1 : -1;
            const next = Math.max(1, Math.min(50, current + delta));
            tourAdultsInput.value = String(next);
            if (tourAdultsValue) {
                tourAdultsValue.textContent = String(next);
            }
            updateAttendeesSummary();
            handleParticipantsChanged();
        }

        function handleParticipantsChanged() {
            if (!tourDateInput?.value) {
                syncTourSlots();
                return;
            }

            tourScheduleInput.value = '';
            tourSlotGrid?.querySelectorAll('[data-tour-slot]').forEach(slotButton => {
                slotButton.classList.remove('active');
                slotButton.setAttribute('aria-pressed', 'false');
            });

            if (tourSlotHint) {
                tourSlotHint.textContent = 'Loading slots...';
            }

            loadTourAvailability(tourDateInput.value);
            syncTourSlots();
        }

        function requiredTypeFields(type) {
            if (type === 'hotel') {
                return ['guests', 'rooms', 'check_in_date', 'check_out_date'];
            }

            if (type === 'restaurant') {
                return ['guests', 'reservation_date', 'reservation_time'];
            }

            if (type === 'tour') {
                return ['adults', 'tour_date', 'tour_schedule'];
            }

            if (type === 'transfer') {
                return ['passengers', 'pickup_date_time', 'pickup_address', 'dropoff_address'];
            }

            return ['passengers', 'pickup_date_time', 'pickup_address', 'dropoff_address'];
        }

        async function submitRequest(event) {
            event.preventDefault();

            if (!state.requestPlace) {
                return;
            }

            // ── Package (tour_visit + transfer) path ─────────────────────────
            const addTransferToggle = document.getElementById('addTransferToggle');
            if (state.requestPlace.type === 'tour_visit' && addTransferToggle?.checked) {
                const pickup      = document.getElementById('tourTransferPickup')?.value?.trim();
                const pickupTime  = document.getElementById('tourTransferPickupTime')?.value;
                const passengers  = document.getElementById('tourTransferPassengers')?.value || '1';
                const tourDate    = requestForm.querySelector('[name="tour_date"]')?.value;

                if (!pickup || !pickupTime || !tourDate) {
                    requestStatus.className = 'request-status error';
                    requestStatus.textContent = 'Please fill in pickup location and pickup time for the transfer.';
                    return;
                }

                const pickupAt = `${tourDate}T${pickupTime}:00`;

                requestStatus.className = 'request-status';
                requestStatus.textContent = 'Sending package request...';
                requestSubmit.disabled = true;

                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
                const formData  = new FormData(requestForm);
                const fd        = Object.fromEntries(formData.entries());

                const packagePayload = {
                    customer_name:    fd.customer_name  || '',
                    customer_email:   fd.customer_email || '',
                    customer_phone:   fd.customer_phone || '',
                    discount_percent: 10,
                    visit: {
                        tour_id:       state.requestPlace.model_id,
                        adults:        parseInt(fd.adults  || '1', 10),
                        children:      parseInt(fd.children || '0', 10),
                        tour_date:     fd.tour_date     || '',
                        tour_schedule: fd.tour_schedule || '',
                        unit_price:    state.requestPlace.unit_price ?? 0,
                    },
                    transfer: {
                        pickup:     pickup,
                        dropoff:    'Bodega La Geria',
                        pickup_at:  pickupAt,
                        passengers: parseInt(passengers, 10),
                    },
                };

                fetch(@json(route('public.explore.packages.store', [], false)), {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: JSON.stringify(packagePayload),
                })
                    .then(async response => {
                        const body = await response.json().catch(() => ({}));
                        if (!response.ok) {
                            const firstError = Object.values(body.errors || {})[0]?.[0];
                            throw new Error(firstError || body.message || 'Package request could not be sent.');
                        }
                        return body;
                    })
                    .then(body => {
                        requestStatus.className = 'request-status success';
                        requestStatus.textContent = `Package ${body.data.reference} created.`;
                        requestSubmit.onclick = () => window.location.assign(body.data.pay_url);
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = `Pay ${body.data.amount_label}`;
                    })
                    .catch(error => {
                        requestStatus.className = 'request-status error';
                        requestStatus.textContent = error.message;
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = 'Send request';
                    });

                return;
            }

            // ── Standard single-item path ─────────────────────────────────────
            requestStatus.className = 'request-status';
            requestStatus.textContent = 'Sending request...';
            requestSubmit.disabled = true;

            const formData = new FormData(requestForm);
            const payload = Object.fromEntries(formData.entries());
            payload.type = ['taxi_route', 'transfer'].includes(state.requestPlace.type) ? state.requestPlace.type : bookingType(state.requestPlace);
            payload.service_id = state.requestPlace.model_id;

            if (state.requestPlace.type === 'taxi_route' && !payload.passengers && payload.adults) {
                payload.passengers = payload.adults;
            }

            if (state.requestPlace.type === 'transfer') {
                const estimate = await estimateTransferPrice(payload);
                if (!estimate?.ok || !estimate?.estimated_price) {
                    throw new Error(estimate?.error || 'We could not calculate this transfer price yet.');
                }

                payload.base_price = String(estimate.estimated_price);
                requestStatus.textContent = `Transfer fare ${Number(estimate.estimated_price).toFixed(2)}€ · ${estimate.pickup_tariff_zone || 'origin'} → ${estimate.dropoff_tariff_zone || 'destination'}`;
            }
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

            fetch(@json(route('public.explore.requests.store', [], false)), {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            })
                .then(async response => {
                    const body = await response.json().catch(() => ({}));

                    if (!response.ok) {
                        const firstError = Object.values(body.errors || {})[0]?.[0];
                        throw new Error(firstError || body.message || 'The request could not be sent.');
                    }

                    return body;
                })
                .then(body => {
                    const remoteStatus = body.data.remote_booking?.status;
                    const payUrl = body.data.payment?.pay_url;
                    const amountLabel = body.data.payment?.amount_label;
                    requestStatus.className = 'request-status success';
                    requestStatus.textContent = remoteStatus === 'created'
                        ? `Request ${body.data.reference} saved.`
                        : remoteStatus === 'failed'
                            ? `Request ${body.data.reference} sent locally. Remote booking failed and can be reviewed.`
                            : `Request ${body.data.reference} sent. Current status: pending.`;
                    requestSubmit.textContent = remoteStatus === 'created' ? 'Saved' : 'Sent';

                    if (remoteStatus === 'created' && payUrl) {
                        requestSubmit.onclick = () => window.location.assign(payUrl);
                        requestSubmit.disabled = false;
                        requestSubmit.textContent = amountLabel ? `Pay ${amountLabel}` : 'Continue to payment';
                    }

                    const warnings = body.data.warnings || [];
                    if (warnings.length) {
                        requestStatus.className = 'request-status';
                        requestStatus.textContent = warnings[0];
                    }
                })
                .catch(error => {
                    requestStatus.className = 'request-status error';
                    requestStatus.textContent = error.message;
                    requestSubmit.disabled = false;
                    requestSubmit.textContent = 'Send request';
                });
        }

        async function estimateTransferPrice(payload) {
            const params = new URLSearchParams({
                pickup_location: payload.pickup_address || '',
                dropoff_location: payload.dropoff_address || '',
                passengers: payload.passengers || '1',
            });

            const response = await fetch(`${@json(route('public.explore.transfer-estimate', [], false))}?${params.toString()}`, {
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const body = await response.json().catch(() => ({}));

            if (!response.ok) {
                throw new Error(body.message || 'Transfer price estimate failed.');
            }

            return body.data?.result || body.data;
        }

        function updateSummary(places, mappedPlaces = mappablePlaces(places)) {
            const noneSelected = state.activeTypes.size === 0;

            const labels = Array.from(state.activeTypes).map(type => {
                if (type === 'hotel')      return 'hotels';
                if (type === 'restaurant') return 'restaurants';
                if (type === 'taxi')       return 'taxi services';
                if (type === 'tour_visit') return 'visit tours';
                if (type === 'taxi_route') return 'taxi routes';
                if (type === 'transfer')   return 'transfers';
                return type;
            }).join(', ');

            mapTitle.textContent = noneSelected ? 'Select a category' : `${places.length} result${places.length === 1 ? '' : 's'}`;
            mapSubtitle.textContent = noneSelected
                ? 'Choose one or more filters above to explore Lanzarote.'
                : places.length
                    ? `${mappedPlaces.length} shown on the map · ${labels}.`
                    : 'No places match the current filters.';
        }

        function bookingType(place) {
            return place.booking_type || place.type;
        }

        function updateCounts(types) {
            Object.entries(types).forEach(([type, count]) => {
                const node = document.querySelector(`[data-count="${type}"]`);
                if (node) {
                    node.textContent = count;
                }
            });
        }

        function fitMap() {
            const places = mappablePlaces(filteredPlaces());
            if (!places.length) {
                mapSubtitle.textContent = 'No selected results have map coordinates yet.';
                return;
            }

            const bounds = L.latLngBounds(places.map(place => [place.latitude, place.longitude]));
            map.fitBounds(bounds, {
                paddingTopLeft: [40, 70],
                paddingBottomRight: [40, 70],
                maxZoom: 14,
                animate: true,
            });
        }

        function markerIcon(place) {
            const config = typeConfig[place.type];
            const active = state.selectedId === place.id ? ' active' : '';

            return L.divIcon({
                className: '',
                html: `<div class="marker-pin${active}" style="--pin-color: ${config.color}"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">${config.icon}</svg></div>`,
                iconSize: [42, 42],
                iconAnchor: [21, 38],
                popupAnchor: [0, -36],
            });
        }

        function refreshMarkerStates() {
            state.markers.forEach((marker, id) => {
                const place = state.places.find(item => item.id === id);
                if (place) {
                    marker.setIcon(markerIcon(place));
                }
            });
        }

        function popupHtml(place) {
            return `
                <div class="popup-card">
                    <img src="${escapeHtml(place.image)}" alt="" onerror="this.onerror=null;this.src='${escapeHtml(typeConfig[place.type].fallback)}';">
                    <div>
                        <strong>${escapeHtml(place.name)}</strong>
                        <span>${escapeHtml(place.address || place.label)}</span>
                    </div>
                </div>
            `;
        }

        function escapeHtml(value) {
            return String(value ?? '').replace(/[&<>"']/g, character => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[character]);
        }
        function select(day){

            alert(day);

        }
    </script>

</x-layouts.standalone>