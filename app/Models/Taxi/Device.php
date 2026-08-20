<?php

namespace App\Models\Taxi;

use App\Events\GpsEvent;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Str;

class Device extends Model
{

    protected $table = 'devices';

    protected $fillable = [
        'traccar_id',
        'usuario_id',
        'taxi_id',
        'name',
        'unique_id',
        'status',
        'last_update',
        'position_id',
        'group_id',
        'phone',
        'model',
        'contact',
        'category',
        'disabled',
        'expires_at',
        'attributes',
        'lat',
        'lng',
    ];

    protected $casts = [
        'traccar_id' => 'integer',
        'status' => 'string',
        'last_update' => 'datetime',
        'position_id' => 'integer',
        'group_id' => 'integer',
        'disabled' => 'boolean',
        'expires_at' => 'datetime',
        'attributes' => 'array',
    ];
    protected $appends = [
    ];

    public function getLocationAttribute()
    {
        return [
            'lat' => (float)$this->lat,
            'lng' => (float)$this->lng,
        ];
    }

    public function setLocationAttribute()
    {
        return [
            'lat' => (float)$this->lat,
            'lng' => (float)$this->lng,
        ];
    }

    protected static function booted()
    {

        static::updating(function ($model) {
            // $model->title = $model->id.' '.$model->departamento->nombre.' '.$model->usuario->nombre;
            $model->last_update = $model->status == 'online' ? now() : $model->last_update;

        });
        static::creating(function ($model) {

            $model->last_update = now();
        });
        static::created(function ($location) {
            //broadcast(new GpsEvent($location));
        });
    }

    public function scopePorUsuario($query, $usuarioId)
    {
        return $query->where('usuario_id', $usuarioId);
    }

    /** @return MorphToMany<Customer, $this> */
    public function usuarios(): MorphToMany
    {
        return $this->morphedByMany(Usuario::class, 'devices');
    }

    public function taxisDispositivos()
    {
        return $this->belongsToMany(TaxiDispositivo::class, 'taxis_dispositivos', 'taxi_id', 'dispositivo_id');
    }

    public function taxistasDispositivos()
    {
        return $this->belongsToMany(TaxistaDispositivo::class, 'taxista_dispositivos', 'taxita_id', 'dispositivo_id');
    }

    public function position()
    {
        return $this->belongsTo(Position::class, 'position_id', 'traccar_id');
    }

    public function taxista()
    {
        return $this->belongsTo(Taxista::class, 'taxista_id');
    }

    public function taxi()
    {
        return $this->belongsTo(Taxi::class, 'taxi_id');
    }

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id')
            ->select(['id', 'nombre', 'tipo_id']);
    }

}
