<?php

namespace App\Models\Taxi;

use App\Events\GpsEvent;
use Illuminate\Database\Eloquent\Model;

class Gps extends Model
{
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

    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public function attendances()
    {
        return $this->belongsTo(Attendance::class);
    }
}
