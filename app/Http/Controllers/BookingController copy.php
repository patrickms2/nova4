<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Ride;
use App\Models\RideRecommendation;
use App\Services\EventTracker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BookingController extends Controller
{
    public function __construct(protected EventTracker $eventTracker) {}

    public function store(Request $request, Offer $offer): RedirectResponse
    {
        $validated = $request->validate([
            'ride_id' => ['nullable', 'integer', 'exists:rides,id'],
            'ride_recommendation_id' => ['nullable', 'integer', 'exists:ride_recommendations,id'],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'party_size' => ['required', 'integer', 'min:1', 'max:12'],
            'booking_for' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $ride = ! empty($validated['ride_id']) ? Ride::find($validated['ride_id']) : null;
        $recommendation = ! empty($validated['ride_recommendation_id']) ? RideRecommendation::find($validated['ride_recommendation_id']) : null;

        $booking = Booking::create([
            'ride_id' => $ride?->id,
            'offer_id' => $offer->id,
            'ride_recommendation_id' => $recommendation?->id,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'] ?? null,
            'customer_phone' => $validated['customer_phone'] ?? null,
            'party_size' => $validated['party_size'],
            'booking_for' => $validated['booking_for'] ?? null,
            'status' => 'confirmed',
            'amount' => $offer->price_from,
            'commission_amount' => $offer->price_from ? round(((float) $offer->price_from) * 0.15, 2) : null,
            'notes' => $validated['notes'] ?? null,
        ]);

        $this->eventTracker->log('booking_started', $ride, $offer, $booking, $recommendation, [
            'party_size' => $booking->party_size,
        ]);

        $this->eventTracker->log('booking_completed', $ride, $offer, $booking, $recommendation, [
            'amount' => $booking->amount,
        ]);

        return redirect()->route('bookings.success', $booking);
    }

    public function success(Booking $booking): View
    {
        $booking->load(['offer', 'ride', 'recommendation']);

        return view('bookings.success', [
            'booking' => $booking,
        ]);
    }
}
