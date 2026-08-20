<?php

namespace App\Services;

use App\Models\Offer;
use App\Models\Ride;
use App\Models\RideRecommendation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecommendationEngine
{
    private const MAX_RELEVANT_DISTANCE_KM = 35.0;

    private const SCORE_PROXIMITY_MAX = 30.0;

    private const SCORE_AVAILABILITY_MAX = 20.0;

    private const SCORE_CONTEXT_MATCH = 18.0;

    private const SCORE_CONTEXT_MISMATCH = -8.0;

    private const SCORE_CONTEXT_AUTO_MAX = 10.0;

    private const SCORE_AUTHENTICITY_MAX = 15.0;

    private const SCORE_POPULARITY_MAX = 12.0;

    private const SCORE_CONVERSION_MAX = 12.0;

    private const SCORE_PRIORITY_MAX = 8.0;

    private const SCORE_TIME_FIT_MAX = 15.0;

    /**
     * Genera 2-3 recomendaciones para un viaje con scoring real y razones explicables.
     */
    public function generateForRide(Ride $ride, ?string $interestType = null): Collection
    {
        $ranked = $this->previewForRide($ride, $interestType);

        return DB::transaction(function () use ($ranked, $ride) {
            $offerIdsToKeep = collect($ranked)
                ->pluck('offer.id')
                ->filter()
                ->values();

            $ride->recommendations()
                ->when(
                    $offerIdsToKeep->isNotEmpty(),
                    fn ($query) => $query->whereNotIn('offer_id', $offerIdsToKeep->all()),
                    fn ($query) => $query
                )
                ->delete();

            return collect($ranked)->map(function ($item, $index) use ($ride) {
                $attributes = [
                    'position' => $index + 1,
                    'score_total' => $item['score_total'],
                    'score_proximity' => $item['score_proximity'],
                    'score_availability' => $item['score_availability'],
                    'score_popularity' => $item['score_popularity'],
                    'score_conversion' => $item['score_conversion'],
                    'score_context' => $item['score_context'],
                    'score_authenticity' => $item['score_authenticity'],
                    'primary_reason' => $item['primary_reason'] === 'near_destination' ? 'top_near_destination' : $item['primary_reason'],
                    'reason_payload' => array_merge($item['reason_payload'] ?? [], [
                        'reason' => $item['primary_reason'] === 'near_destination' ? 'top_near_destination' : $item['primary_reason'],
                        'score' => round($item['score_total']),
                    ]),
                    'was_viewed' => true,
                ];

                $recommendation = RideRecommendation::query()->updateOrCreate(
                    [
                        'ride_id' => $ride->id,
                        'offer_id' => $item['offer']->id,
                    ],
                    $attributes,
                );

                if ($recommendation->wasRecentlyCreated) {
                    $item['offer']->increment('times_recommended');
                }

                return $recommendation;
            });
        });
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function previewForRide(Ride $ride, ?string $interestType = null): Collection
    {
        $offers = Offer::query()
            ->where('status', 'published')
            ->where('available_now', true)
            ->get();

        $ranked = $offers->map(function (Offer $offer) use ($ride, $interestType) {
            $distanceInKilometers = $this->distanceBetweenRideAndOffer($ride, $offer);
            $proximity = $this->scoreProximity($ride, $offer, $distanceInKilometers);
            $availability = $this->scoreAvailability($offer);
            $popularity = $this->scorePopularity($offer);
            $conversion = $this->scoreConversion($offer);
            $priority = $this->scorePriority($offer);
            $context = $this->scoreContext($ride, $offer, $interestType);
            $authenticity = $this->scoreAuthenticity($offer);
            $timeFit = $this->scoreTimeFit($ride, $offer);
            $interestMatch = $this->isInterestMatch($offer, $interestType);

            $total = $proximity
                + $availability
                + $popularity
                + $conversion
                + $priority
                + $context
                + $authenticity
                + $timeFit;

            [$primaryReason, $payload] = $this->buildReason($ride, $offer, [
                'proximity' => $proximity,
                'availability' => $availability,
                'popularity' => $popularity,
                'conversion' => $conversion,
                'priority' => $priority,
                'context' => $context,
                'authenticity' => $authenticity,
                'time_fit' => $timeFit,
                'distance_km' => $distanceInKilometers,
                'interest_type' => $interestType,
            ]);

            return [
                'offer' => $offer,
                'distance_km' => $distanceInKilometers,
                'interest_match' => $interestMatch,
                'score_total' => $total,
                'score_proximity' => $proximity,
                'score_availability' => $availability,
                'score_popularity' => $popularity,
                'score_conversion' => $conversion,
                'score_context' => $context,
                'score_authenticity' => $authenticity,
                'score_priority' => $priority,
                'score_time_fit' => $timeFit,
                'primary_reason' => $primaryReason,
                'reason_payload' => $payload,
            ];
        })
            ->filter(fn (array $item): bool => ! $this->shouldExcludeByDistance($item['distance_km']));

        if (filled($interestType)) {
            $ranked = $ranked
                ->sortBy(fn (array $item): array => [
                    $item['interest_match'] ? 0 : 1,
                    -1 * (int) round($item['score_total'] * 100),
                    $item['distance_km'] ?? 9999,
                ])
                ->take(3)
                ->values();
        } else {
            $ranked = $ranked
                ->sortBy(fn (array $item): array => [
                    -1 * (int) round($item['score_total'] * 100),
                    $item['distance_km'] ?? 9999,
                ])
                ->take(3)
                ->values();
        }

        return collect($ranked);
    }

    protected function scoreProximity(Ride $ride, Offer $offer, ?float $distanceInKilometers = null): float
    {
        if ($distanceInKilometers !== null) {
            return match (true) {
                $distanceInKilometers <= 1 => self::SCORE_PROXIMITY_MAX,
                $distanceInKilometers <= 3 => 27.0,
                $distanceInKilometers <= 5 => 24.0,
                $distanceInKilometers <= 10 => 18.0,
                $distanceInKilometers <= 20 => 12.0,
                default => 4.0,
            };
        }

        $destination = Str::lower((string) $ride->destination_label);
        $location = Str::lower((string) $offer->location_label);

        if ($destination !== '' && Str::contains($location, $destination)) {
            return self::SCORE_PROXIMITY_MAX;
        }

        if (Str::contains(Str::lower($offer->title), $destination)) {
            return 25.0;
        }

        return 10.0; // Base proximity
    }

    protected function distanceBetweenRideAndOffer(Ride $ride, Offer $offer): ?float
    {
        if (
            blank($ride->destination_lat)
            || blank($ride->destination_lng)
            || blank($offer->lat)
            || blank($offer->lng)
        ) {
            return null;
        }

        return $this->calculateDistanceInKilometers(
            (float) $ride->destination_lat,
            (float) $ride->destination_lng,
            (float) $offer->lat,
            (float) $offer->lng,
        );
    }

    protected function calculateDistanceInKilometers(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
    ): float {
        $earthRadiusInKilometers = 6371.0;

        $latitudeDelta = deg2rad($destinationLatitude - $originLatitude);
        $longitudeDelta = deg2rad($destinationLongitude - $originLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($originLatitude))
            * cos(deg2rad($destinationLatitude))
            * sin($longitudeDelta / 2) ** 2;

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadiusInKilometers * $c;
    }

    protected function shouldExcludeByDistance(?float $distanceInKilometers): bool
    {
        if ($distanceInKilometers === null) {
            return false;
        }

        return $distanceInKilometers > self::MAX_RELEVANT_DISTANCE_KM;
    }

    protected function scoreAvailability(Offer $offer): float
    {
        return $offer->available_now ? self::SCORE_AVAILABILITY_MAX : 0.0;
    }

    protected function scorePopularity(Offer $offer): float
    {
        return min(self::SCORE_POPULARITY_MAX, (float) (($offer->times_booked ?? 0) / 5));
    }

    protected function scoreConversion(Offer $offer): float
    {
        return min(self::SCORE_CONVERSION_MAX, (float) ($offer->avg_conversion_rate ?? 0));
    }

    protected function scorePriority(Offer $offer): float
    {
        return min(self::SCORE_PRIORITY_MAX, (float) (($offer->priority_score ?? 50) / 12.5));
    }

    protected function scoreAuthenticity(Offer $offer): float
    {
        return min(self::SCORE_AUTHENTICITY_MAX, (float) (($offer->authenticity_score ?? 50) / 6.66));
    }

    protected function scoreContext(Ride $ride, Offer $offer, ?string $interestType = null): float
    {
        $tags = collect($offer->context_tags ?? []);

        if ($interestType) {
            return match ($interestType) {
                'gastronomy' => $this->isInterestMatch($offer, 'gastronomy') ? self::SCORE_CONTEXT_MATCH : self::SCORE_CONTEXT_MISMATCH,
                'leisure' => $this->isInterestMatch($offer, 'leisure') ? self::SCORE_CONTEXT_MATCH : self::SCORE_CONTEXT_MISMATCH,
                'shopping' => $this->isInterestMatch($offer, 'shopping') ? self::SCORE_CONTEXT_MATCH : self::SCORE_CONTEXT_MISMATCH,
                'information' => $this->isInterestMatch($offer, 'information') ? self::SCORE_CONTEXT_MATCH : self::SCORE_CONTEXT_MISMATCH,
                default => 5.0,
            };
        }

        $hour = $this->resolveRideHour($ride);
        $score = 0.0;

        if ($hour >= 18 && $hour <= 20 && $tags->contains('sunset')) {
            $score += 10.0;
        }

        if ($hour >= 19 && $offer->category === 'restaurant') {
            $score += 8.0;
        }

        if ($ride->source_channel === 'airport' && $tags->contains('after_ride')) {
            $score += 10.0;
        }

        return min(self::SCORE_CONTEXT_AUTO_MAX, $score);
    }

    protected function scoreTimeFit(Ride $ride, Offer $offer): float
    {
        $duration = (int) ($offer->duration_minutes ?? $offer->duration ?? 0);

        if ($duration <= 0) {
            return 6.0;
        }

        return match (true) {
            $duration <= 20 => self::SCORE_TIME_FIT_MAX,
            $duration <= 45 => 12.0,
            $duration <= 90 => 9.0,
            $duration <= 180 => 6.0,
            default => 2.0,
        };
    }

    protected function isInterestMatch(Offer $offer, ?string $interestType): bool
    {
        if (blank($interestType)) {
            return true;
        }

        $tags = collect($offer->context_tags ?? [])->map(fn (mixed $tag): string => Str::lower((string) $tag));
        $experienceType = Str::lower((string) ($offer->experience_type ?? ''));
        $category = Str::lower((string) $offer->category);

        return match ($interestType) {
            'gastronomy' => $experienceType === 'gastronomy'
                || $category === 'restaurant'
                || $tags->contains('food')
                || $tags->contains('gastronomy'),
            'leisure' => $experienceType === 'leisure'
                || in_array($category, ['experience', 'activity'], true)
                || $tags->contains('leisure')
                || $tags->contains('experience'),
            'shopping' => $experienceType === 'shopping'
                || $category === 'product'
                || $tags->contains('shopping')
                || $tags->contains('artisan'),
            'information' => $experienceType === 'information'
                || $category === 'service'
                || $tags->contains('information')
                || $tags->contains('secret_spot'),
            default => false,
        };
    }

    /**
     * @return array{
     *     matched_profiles: list<string>,
     *     geo_ready: bool,
     *     geo_status: string,
     *     tags: list<string>,
     *     priority_band: string,
     *     reasons: list<string>
     * }
     */
    public function auditOfferSelection(Offer $offer): array
    {
        $matchedProfiles = collect(['gastronomy', 'leisure', 'shopping', 'information'])
            ->filter(fn (string $profile): bool => $this->isInterestMatch($offer, $profile))
            ->values()
            ->all();

        $tags = collect($offer->context_tags ?? [])
            ->map(fn (mixed $tag): string => (string) $tag)
            ->filter()
            ->values()
            ->all();

        $geoReady = filled($offer->lat) && filled($offer->lng);
        $priorityBand = match (true) {
            (int) $offer->priority_score >= 90 => 'Muy alta',
            (int) $offer->priority_score >= 70 => 'Alta',
            (int) $offer->priority_score >= 40 => 'Media',
            default => 'Baja',
        };

        $reasons = [];

        if ($matchedProfiles !== []) {
            $reasons[] = 'Encaja con perfiles: '.implode(', ', $matchedProfiles);
        } else {
            $reasons[] = 'No tiene un perfil claro; entra solo por fallback.';
        }

        $reasons[] = $geoReady
            ? 'Tiene coordenadas y puede competir por cercanía real.'
            : 'No tiene coordenadas; cae a fallback textual.';

        if ($tags !== []) {
            $reasons[] = 'Context tags: '.implode(', ', $tags);
        } else {
            $reasons[] = 'Sin context tags; pierde señal temática.';
        }

        if (filled($offer->experience_type)) {
            $reasons[] = 'Experience type: '.$offer->experience_type;
        }

        return [
            'matched_profiles' => $matchedProfiles,
            'geo_ready' => $geoReady,
            'geo_status' => $geoReady ? 'Coordenadas listas' : 'Sin coordenadas',
            'tags' => $tags,
            'priority_band' => $priorityBand,
            'reasons' => $reasons,
        ];
    }

    protected function buildReason(Ride $ride, Offer $offer, array $scores): array
    {
        if ($scores['context'] >= 8 && ! empty($scores['interest_type'])) {
            return ['matched_'.$scores['interest_type'].'_interest', ['interest_type' => $scores['interest_type']]];
        }

        if ($scores['proximity'] >= 25) {
            return ['top_near_destination', [
                'distance_km' => $scores['distance_km'] ? round($scores['distance_km'], 2) : null,
                'distance_minutes' => $this->estimateDistanceMinutes($scores['distance_km']),
            ]];
        }

        if ($scores['authenticity'] >= 12 || ($offer->authenticity_score ?? 0) >= 80) {
            return ['strong_local_value', ['authenticity_score' => $offer->authenticity_score]];
        }

        if ($scores['context'] >= 8) {
            $hour = $this->resolveRideHour($ride);
            if ($hour >= 18) {
                return ['good_for_arrival_window', ['context' => 'sunset_dinner']];
            }

            return ['matches_channel', ['channel' => $ride->source_channel]];
        }

        if (($scores['time_fit'] ?? 0) >= 12) {
            return ['fits_available_time', ['duration_minutes' => (int) ($offer->duration_minutes ?? $offer->duration ?? 0)]];
        }

        if ($scores['conversion'] >= 10) {
            return ['high_conversion', ['conversion_rate' => $offer->avg_conversion_rate]];
        }

        if (($scores['priority'] ?? 0) >= 6 || $offer->is_featured) {
            return ['featured_partner', ['partner_level' => 'premium']];
        }

        return ['available_now', ['available_now' => true]];
    }

    protected function estimateDistanceMinutes(?float $distanceInKilometers): ?int
    {
        if ($distanceInKilometers === null) {
            return null;
        }

        return max(2, (int) round($distanceInKilometers * 3.5));
    }

    protected function resolveRideHour(Ride $ride): int
    {
        return optional($ride->scheduled_for)->hour ?? now()->hour;
    }
}
