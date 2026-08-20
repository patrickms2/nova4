<?php

namespace App\Models\Taxi;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsuarioDireccion extends Model
{
    use HasFactory;

    protected $table = 'usuarios_direcciones';
    protected $primaryKey = 'id';

    protected static function booted(): void
    {
        static::saving(function (UsuarioDireccion $model): void {
            if (blank($model->title) && filled($model->name)) {
                $model->title = $model->name;
            }
        });
    }

    protected $fillable = [
        'name',
        'title',
        'lat',
        'lng',
        'street',
        'number',
        'city',
        'state',
        'zip',
        'country',
        'address',
        'formatted_address',
        'phone',
        'website',
        'place_id',
        'marker_type',
        'processed',
        'location',
        'description',
        'usuario_id',
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

    function setLocationAttribute(mixed $location): void
    {
        if (is_string($location)) {
            $decoded = json_decode($location, true);
            $location = is_array($decoded) ? $decoded : null;
        }

        if (is_array($location) && isset($location['lat'], $location['lng'])) {
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

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function getEmployeeIdAttribute(): ?int
    {
        return isset($this->attributes['usuario_id']) ? (int) $this->attributes['usuario_id'] : null;
    }

    public function setEmployeeIdAttribute(?int $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }
}
