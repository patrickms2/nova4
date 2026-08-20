<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Usuario;

class EstadosUsuario extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $table = 'estados_usuarios';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'estado_id');
    }
}
