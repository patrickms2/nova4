<?php

namespace App\Models\Taxi;

use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    use HasFactory;
    use InteractsWithMaps;

	protected $table = 'usuarios_direcciones';
    protected $primaryKey = 'id';

    protected $fillable = [
        'name',
        'lat',
        'lng',
        'street',
        'city',
        'state',
        'zip',
        'address',
        'processed',
        'location',
        'description',
        'usuario_id'
    ];

    protected $appends = [
        'location',
    ];


    protected $casts = [
        'processed' => 'bool',
    ];

    /**
     * The following code was generated for use with Filament Google Maps
     *
     * php artisan fgm:model-code Location --lat=lat --lng=lng --location=location --terse
     */

    function getLocationAttribute(): array
    {
        return [
            "lat" => (float)$this->lat,
            "lng" => (float)$this->lng,
        ];
    }

    function setLocationAttribute(?array $location): void
    {
        if (is_array($location))
        {
            $this->attributes['lat'] = $location['lat'];
            $this->attributes['lng'] = $location['lng'];
            unset($this->attributes['location']);
            }
        }

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

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
}
