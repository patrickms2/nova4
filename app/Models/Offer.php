<?php

namespace App\Models;

use App\Services\SpeechAnalysisService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Offer extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'slug',
        'category',
        'excerpt',
        'description',
        'location_label',
        'lat',
        'lng',
        'price_from',
        'duration_minutes',
        'image_url',
        'status',
        'is_featured',
        'priority_score',
        'available_now',
        'context_tags',
        'avg_conversion_rate',
        'times_recommended',
        'times_clicked',
        'times_booked',
        'authenticity_score',
        'local_tag',
        'experience_type',
        'price_unit',
    ];

    protected $casts = [
        'lat' => 'decimal:7',
        'lng' => 'decimal:7',
        'price_from' => 'decimal:2',
        'is_featured' => 'boolean',
        'available_now' => 'boolean',
        'context_tags' => 'array',
        'avg_conversion_rate' => 'decimal:2',
        'authenticity_score' => 'integer',
    ];

    /**
     * @return array{lat: float, lng: float}|null
     */
    public function getLocationAttribute(): ?array
    {
        if (blank($this->lat) || blank($this->lng)) {
            return null;
        }

        return [
            'lat' => (float) $this->lat,
            'lng' => (float) $this->lng,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Offer $offer) {
            if (blank($offer->uuid)) {
                $offer->uuid = (string) Str::uuid();
            }

            if (blank($offer->slug)) {
                $offer->slug = Str::slug($offer->title);
            }
        });

        // Completar imagen y datos de ubicación si faltan al guardar
        static::saving(function (Offer $offer) {
            try {
                if (blank($offer->image_url)) {
                    $text = implode(' · ', array_filter([
                        $offer->title,
                        $offer->excerpt,
                        $offer->description,
                        $offer->location_label,
                    ]));

                    if (trim($text) !== '') {
                        $svc = app(SpeechAnalysisService::class);
                        $analysis = $svc->analyze($text);
                        if (! empty($analysis['suggested_image_url'])) {
                            $offer->image_url = $analysis['suggested_image_url'];
                        }
                        if (blank($offer->location_label) && ! empty($analysis['suggested_address'])) {
                            $offer->location_label = $analysis['suggested_address'];
                        }
                        if (blank($offer->lat) && ! empty($analysis['suggested_latitude'])) {
                            $offer->lat = $analysis['suggested_latitude'];
                        }
                        if (blank($offer->lng) && ! empty($analysis['suggested_longitude'])) {
                            $offer->lng = $analysis['suggested_longitude'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                // Evitar que falle el guardado si el análisis lanza una excepción
            }
        });
    }

    public function setLocationAttribute(mixed $location): void
    {
        if (is_string($location)) {
            $decoded = json_decode($location, true);
            $location = is_array($decoded) ? $decoded : null;
        }

        if (! is_array($location) || ! isset($location['lat'], $location['lng'])) {
            return;
        }

        $this->attributes['lat'] = $location['lat'];
        $this->attributes['lng'] = $location['lng'];
    }

    /**
     * @return array{lat: string, lng: string}
     */
    public static function getLatLngAttributes(): array
    {
        return [
            'lat' => 'lat',
            'lng' => 'lng',
        ];
    }

    public static function getComputedLocation(): string
    {
        return 'location';
    }

    public function recommendations(): HasMany
    {
        return $this->hasMany(RideRecommendation::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(BookingRide::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }
}
