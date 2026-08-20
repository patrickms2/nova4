<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Servicio;

class EstadosServicio extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'estados_servicios';

    protected $fillable = [
        'nombre',
        'estado',
        'color',
        'orden',
    ];

    public function servicios()
    {
        return $this->hasMany(Servicio::class, 'estado_id');
    }
}
