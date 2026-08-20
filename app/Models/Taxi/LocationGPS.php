<?php

namespace App\Models\Taxi;

use App\Events\GpsEvent;
use Cheesegrits\FilamentGoogleMaps\Concerns\InteractsWithMaps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LocationGPS extends Model
{
    use HasFactory;
    use InteractsWithMaps;

	protected $table = 'locations';
    protected $primaryKey = 'id';

    protected $fillable = [
        'usuario_id',
        'latitude',
        'longitude',
        'speed',
    ];

    protected static function booted()
    {
        static::created(function ($location) {
            broadcast(new GpsEvent($location));
        });

    }
    protected $appends = [
        'location',
    ];
    protected $casts = [
        'processed' => 'bool',
    ];
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'usuario_id');
    }
}
