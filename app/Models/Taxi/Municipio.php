<?php
// app/Models/Municipio.php
namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Model;

class Municipio extends Model
{
    protected $table = 'municipios';

    protected $fillable = [
        'nombre', 'color', 'estado', 'foto', 'providerId', 'codestado', 'orden'
    ];

    public function usuario()
    {
        return $this->hasMany(Usuario::class, 'municipio_id');
    }
}
