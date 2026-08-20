<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Offer;
use App\Models\Ride;

class HomeController extends Controller
{
    public function __invoke()
    {
        $rides = Ride::count();
        $views = Event::where('event_type', 'recommendation_viewed')->count();
        $clicks = Event::where('event_type', 'recommendation_clicked')->count();
        $bookings = Booking::count();
        $gmv = (float) Booking::sum('amount');
        $commission = (float) Booking::sum('commission_amount');

        return view('home', [
            'featuredOffers' => Offer::query()->where('status', 'published')->orderByDesc('is_featured')->orderByDesc('priority_score')->take(3)->get(),
            'metrics' => [
                'rides' => $rides,
                'views' => $views,
                'clicks' => $clicks,
                'bookings' => $bookings,
                'gmv' => $gmv,
                'commission' => $commission,
                'ride_to_booking_rate' => $rides > 0 ? round(($bookings / $rides) * 100, 1) : 0,
            ],
        ]);
    }
}
