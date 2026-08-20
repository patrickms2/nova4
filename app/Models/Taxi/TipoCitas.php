<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoCitas extends Model
{
    use HasFactory;

    protected $table = 'tipos_citas';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'slug',
        'color',
        'icono',
        'orden',
        'observaciones',

    ];

    public function citas()
    {
        return $this->hasMany(Cita::class, 'tipo_id');
    }
}
