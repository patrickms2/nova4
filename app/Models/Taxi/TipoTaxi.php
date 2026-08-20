<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTaxi extends Model
{
    use HasFactory;

    protected $table = 'taxi_tipos';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'color',
        'codestado',
        'preferenceId'
    ];

    /**
     * Relación con servicios
     */
    public function taxis()
    {
        return $this->hasMany(Taxi::class, 'tipotaxi');
    }
    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'tipotaxi_id');
    }
}
