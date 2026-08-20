<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTaxis extends Model
{
    use HasFactory;

    protected $table = 'taxi_tipos';

    protected $fillable = [
        'nombre',
        'color',
        'estado',
        'capacidad',
        'preferenceId',
        'preferenceName',
        'version',
        'orden',
        'icono',
        'observaciones',
    ];
    public function taxis()
    {
        return $this->hasMany(Taxi::class, 'tipotaxi');
    }
    public function servicios(){
        return $this->hasMany(Servicio::class, 'tipotaxi_id');
    }
}
