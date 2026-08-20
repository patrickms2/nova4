<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Ride;
use App\Models\RideRecommendation;
use App\Services\EventTracker;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OfferController extends Controller
{
    public function __construct(protected EventTracker $eventTracker) {}

    public function show(Request $request, Offer $offer): View
    {
        $ride = $request->integer('ride') ? Ride::find($request->integer('ride')) : null;
        $recommendation = $request->integer('recommendation') ? RideRecommendation::with('offer', 'ride')->find($request->integer('recommendation')) : null;

        if ($recommendation && $recommendation->offer_id === $offer->id) {
            $this->eventTracker->logOncePerSession(
                'recommendation_clicked',
                'rec-clicked-'.$recommendation->id,
                $ride,
                $offer,
                null,
                $recommendation,
                ['position' => $recommendation->position]
            );

            if (! $recommendation->was_clicked) {
                $recommendation->forceFill([
                    'was_clicked' => true,
                    'clicked_at' => now(),
                ])->save();
            }
        }

        return view('offers.show', [
            'offer' => $offer,
            'ride' => $ride,
            'recommendation' => $recommendation,
        ]);
    }
}
