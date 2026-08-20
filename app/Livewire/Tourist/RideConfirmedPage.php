<?php

namespace App\Livewire\Tourist;

use App\Models\Ride;
use App\Services\EventLogger;
use App\Services\RecommendationEngine;
use Livewire\Component;

class RideConfirmedPage extends Component
{
    public Ride $ride;

    public ?string $interestType = null;

    public bool $showSuggestions = false;

    public bool $autoMode = true;

    public ?string $autoReason = null;

    public array $interestOptions = [
        'gastronomy' => 'Gastronomía',
        'leisure' => 'Ocio',
        'shopping' => 'Compras',
        'information' => 'Información',
    ];

    public function mount(Ride $ride): void
    {
        $this->ride = $ride->load('recommendations.offer');

        if ($this->ride->interest_type && $this->ride->recommendations->count() > 0) {
            $this->interestType = $this->ride->interest_type;
            $this->showSuggestions = true;
            $this->autoReason = 'restored_existing_context';

            return;
        }

        if (! $this->autoMode) {
            return;
        }

        [$interestType, $reason] = $this->inferInterestFromContext($this->ride);

        if (! $interestType || ! array_key_exists($interestType, $this->interestOptions)) {
            return;
        }

        $this->interestType = $interestType;
        $this->autoReason = $reason;

        app(EventLogger::class)->log('auto_interest_inferred', [
            'ride_id' => $this->ride->id,
            'meta' => [
                'interest_type' => $interestType,
                'auto_reason' => $reason,
                'destination' => $this->ride->destination_label,
                'context_zone' => $this->ride->context_zone,
            ],
        ]);

        $this->continueToSuggestions(
            app(RecommendationEngine::class),
            app(EventLogger::class),
        );
    }

    public function selectInterest(string $type): void
    {
        if (! array_key_exists($type, $this->interestOptions)) {
            return;
        }

        $this->interestType = $type;
        $this->autoReason = 'manual_selection';

        $this->continueToSuggestions(
            app(RecommendationEngine::class),
            app(EventLogger::class),
        );
    }

    public function continueToSuggestions(RecommendationEngine $engine, EventLogger $events): void
    {
        if (! $this->interestType || ! array_key_exists($this->interestType, $this->interestOptions)) {
            $this->addError('interestType', 'Selecciona lo que más te interesa ahora.');

            return;
        }

        $resolvedZone = $this->ride->context_zone ?: $this->resolveContextZone($this->ride);

        $this->ride->update([
            'interest_type' => $this->interestType,
            'context_zone' => $resolvedZone,
        ]);

        $this->ride->interest_type = $this->interestType;
        $this->ride->context_zone = $resolvedZone;

        $this->showSuggestions = true;

        if ($this->ride->relationLoaded('recommendations')) {
            $existing = $this->ride->recommendations->count();
        } else {
            $existing = $this->ride->recommendations()->count();
        }

        if ($existing === 0) {
            $recommendations = $engine->generateForRide($this->ride, $this->interestType);

            foreach ($recommendations as $recommendation) {
                $events->log('recommendation_viewed', [
                    'ride_id' => $this->ride->id,
                    'offer_id' => $recommendation->offer_id,
                    'ride_recommendation_id' => $recommendation->id,
                    'meta' => [
                        'interest_type' => $this->interestType,
                        'context_zone' => $this->ride->context_zone,
                        'auto_reason' => $this->autoReason,
                    ],
                ]);
            }
        }

        $this->ride = $this->ride->fresh()->load('recommendations.offer');
    }

    public function backToInterestSelection(): void
    {
        $this->showSuggestions = false;

        // Mantener interés pero permitir cambio UX
        // (no resetear interestType para UX fluido)
    }

    public function useManualSelection(): void
    {
        $this->showSuggestions = false;
        $this->autoMode = false;
        $this->autoReason = 'manual_override';
    }

    protected function inferInterestFromContext(Ride $ride): array
    {
        $hour = $this->resolveRideHour($ride);
        $destination = str((string) $ride->destination_label)->lower()->value();
        $zone = str((string) $ride->context_zone)->lower()->value();

        if ($hour >= 12 && $hour <= 16) {
            return ['gastronomy', 'lunch_window'];
        }

        if ($hour >= 19) {
            return ['gastronomy', 'dinner_window'];
        }

        if (str_contains($destination, 'playa') || str_contains($destination, 'puerto') || str_contains($zone, 'beach')) {
            return ['leisure', 'coastal_arrival'];
        }

        if (str_contains($destination, 'marina') || str_contains($destination, 'shopping') || str_contains($zone, 'retail')) {
            return ['shopping', 'retail_zone'];
        }

        if (str_contains($destination, 'centro') || str_contains($destination, 'casco') || str_contains($zone, 'historic')) {
            return ['information', 'city_orientation'];
        }

        return ['leisure', 'default_arrival_context'];
    }

    protected function resolveRideHour(Ride $ride): int
    {
        return (int) now()->timezone(config('app.timezone', 'Atlantic/Canary'))->format('G');
    }

    protected function resolveContextZone(Ride $ride): string
    {
        $destination = str((string) $ride->destination_label)->lower()->value();

        return match (true) {
            str_contains($destination, 'puerto del carmen') => 'tourism_puerto_del_carmen',
            str_contains($destination, 'costa teguise') => 'tourism_costa_teguise',
            str_contains($destination, 'playa blanca') => 'tourism_playa_blanca',
            str_contains($destination, 'arrecife') => 'city_arrecife',
            str_contains($destination, 'geria') => 'wine_lands_geria',
            default => 'general_arrival_zone',
        };
    }

    public function selectedInterestLabel(): ?string
    {
        if (! $this->interestType) {
            return null;
        }

        return $this->interestOptions[$this->interestType] ?? null;
    }

    public function render()
    {
        return view('livewire.tourist.ride-confirmed-page');
    }
}
