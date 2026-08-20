<?php

namespace App\Livewire\Tourist;

use App\Models\Ride;
use App\Services\EventLogger;
use Illuminate\Support\Str;
use Livewire\Component;

class HomePage extends Component
{
    private const LANZAROTE_BOUNDS = [
        'min_lat' => 28.780,
        'max_lat' => 29.470,
        'min_lng' => -13.950,
        'max_lng' => -13.370,
    ];

    public string $destination = '';

    public string $detectedPickup = 'Aeropuerto César Manrique';

    public ?string $pickupLat = null;

    public ?string $pickupLng = null;

    public ?string $destinationLat = null;

    public ?string $destinationLng = null;

    public bool $isDetecting = false;

    public function mount(): void
    {
        // La detección se maneja por Alpine en la vista para feedback visual inmediato
    }

    public function detectLocation(): void
    {
        $this->isDetecting = true;
    }

    public function setPickupLocation(string $label, ?string $lat = null, ?string $lng = null): void
    {
        $this->detectedPickup = $label;
        $this->pickupLat = $lat ?: null;
        $this->pickupLng = $lng ?: null;
        $this->isDetecting = false;
    }

    public function setDestinationLocation(string $label, ?string $lat = null, ?string $lng = null): void
    {
        $this->destination = $label;

        if ($lat && $lng) {
            $this->destinationLat = $lat;
            $this->destinationLng = $lng;

            return;
        }

        $this->destinationLat = null;
        $this->destinationLng = null;
    }

    public function submit(EventLogger $events)
    {
        if ($this->destination && (! $this->destinationLat || ! $this->destinationLng)) {
            $this->addError('destination', 'Selecciona un destino válido de la lista.');

            return null;
        }

        $this->validate([
            'detectedPickup' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
        ]);

        $hasLanzaroteValidationError = false;

        if (! $this->isInsideLanzarote($this->pickupLat, $this->pickupLng)) {
            $this->addError('detectedPickup', 'La recogida debe estar en Lanzarote.');
            $hasLanzaroteValidationError = true;
        }

        if (! $this->isInsideLanzarote($this->destinationLat, $this->destinationLng)) {
            $this->addError('destination', 'El destino debe estar en Lanzarote.');
            $hasLanzaroteValidationError = true;
        }

        if ($hasLanzaroteValidationError) {
            return null;
        }

        $events->log('geo_validation_passed', [
            'pickup' => [$this->pickupLat, $this->pickupLng],
            'destination' => [$this->destinationLat, $this->destinationLng],
        ]);

        $ride = Ride::create([
            'uuid' => (string) Str::uuid(),
            'pickup_label' => $this->detectedPickup,
            'pickup_lat' => $this->pickupLat,
            'pickup_lng' => $this->pickupLng,
            'destination_label' => $this->destination,
            'destination_lat' => $this->destinationLat,
            'destination_lng' => $this->destinationLng,
            'status' => 'confirmed',
            'eta_minutes' => rand(3, 6),
            'source_channel' => 'app',
            'locale' => 'es',
        ]);

        $events->log('request_created', [
            'ride_id' => $ride->id,
            'meta' => [
                'pickup' => $ride->pickup_label,
                'destination' => $ride->destination_label,
            ],
        ]);

        return $this->redirectRoute('rides.confirmed', ['ride' => $ride->id], navigate: true);
    }

    public function render()
    {
        return view('livewire.tourist.home-page');
    }

    private function isInsideLanzarote(?string $latitude, ?string $longitude): bool
    {
        if (blank($latitude) || blank($longitude)) {
            return false;
        }

        $latitudeValue = (float) $latitude;
        $longitudeValue = (float) $longitude;

        return $latitudeValue >= self::LANZAROTE_BOUNDS['min_lat']
            && $latitudeValue <= self::LANZAROTE_BOUNDS['max_lat']
            && $longitudeValue >= self::LANZAROTE_BOUNDS['min_lng']
            && $longitudeValue <= self::LANZAROTE_BOUNDS['max_lng'];
    }
}
