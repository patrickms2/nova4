<?php

namespace App\Http\Controllers;

use App\Models\Ride;
use App\Models\RideRecommendation;
use App\Services\EventTracker;
use App\Services\RecommendationEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RideController extends Controller
{
    public function __construct(
        protected RecommendationEngine $recommendationEngine,
        protected EventTracker $eventTracker,
    ) {}

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'pickup_label' => ['required', 'string', 'max:255'],
            'destination_label' => ['required', 'string', 'max:255'],
            'scheduled_for' => ['nullable', 'date'],
            'source_channel' => ['required', 'in:app,hotel,reception,web'],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        $ride = Ride::create([
            'pickup_label' => $validated['pickup_label'],
            'destination_label' => $validated['destination_label'],
            'scheduled_for' => $validated['scheduled_for'] ?? null,
            'status' => 'confirmed',
            'eta_minutes' => $this->estimateEta($validated['pickup_label'], $validated['destination_label']),
            'source_channel' => $validated['source_channel'],
            'context_zone' => $this->resolveZone($validated['destination_label']),
            'locale' => $validated['locale'] ?? app()->getLocale(),
        ]);

        $this->eventTracker->log('request_created', $ride, meta: [
            'source_channel' => $ride->source_channel,
            'context_zone' => $ride->context_zone,
        ]);

        $this->syncRecommendations($ride);

        return redirect()->route('rides.confirmed', $ride);
    }

    public function confirmed(Ride $ride): View
    {
        $ride->load(['recommendations.offer']);

        if ($ride->recommendations->isEmpty()) {
            $this->syncRecommendations($ride);
            $ride->load(['recommendations.offer']);
        }

        foreach ($ride->recommendations as $recommendation) {
            $this->eventTracker->logOncePerSession(
                'recommendation_viewed',
                'rec-viewed-'.$recommendation->id,
                $ride,
                $recommendation->offer,
                null,
                $recommendation,
                ['position' => $recommendation->position]
            );

            if (! $recommendation->was_viewed) {
                $recommendation->forceFill(['was_viewed' => true])->save();
            }
        }

        return view('rides.confirmed', [
            'ride' => $ride,
            'recommendations' => $ride->recommendations,
        ]);
    }

    protected function syncRecommendations(Ride $ride): void
    {
        $offers = $this->recommendationEngine->recommendFor($ride, 3);

        $ride->recommendations()->delete();

        foreach ($offers as $index => $offer) {
            RideRecommendation::create([
                'ride_id' => $ride->id,
                'offer_id' => $offer->id,
                'position' => $index + 1,
                'score' => $offer->demo_score,
                'reason' => $offer->demo_reason,
                'was_viewed' => false,
                'was_clicked' => false,
            ]);
        }
    }

    protected function resolveZone(string $destinationLabel): string
    {
        $normalized = mb_strtolower($destinationLabel);

        return match (true) {
            str_contains($normalized, 'puerto del carmen') => 'puerto del carmen',
            str_contains($normalized, 'playa blanca') => 'playa blanca',
            str_contains($normalized, 'costa teguise') => 'costa teguise',
            str_contains($normalized, 'arrecife') => 'arrecife',
            str_contains($normalized, 'marina') => 'marina',
            str_contains($normalized, 'playa'), str_contains($normalized, 'beach') => 'playa',
            str_contains($normalized, 'old town'), str_contains($normalized, 'casco antiguo') => 'casco antiguo',
            str_contains($normalized, 'centro'), str_contains($normalized, 'center'), str_contains($normalized, 'centre') => 'centro',
            default => trim($destinationLabel),
        };
    }

    protected function estimateEta(string $pickup, string $destination): int
    {
        $seed = strlen($pickup) + strlen($destination);

        return max(4, min(16, ($seed % 13) + 4));
    }
}
