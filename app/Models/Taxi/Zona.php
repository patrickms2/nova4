<?php
namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zona extends Model
{
    use HasFactory;

    protected $table = 'zonas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'coordenadas',
        'color',
        'activo',
    ];

    protected $casts = [
        'coordenadas' => 'array',
        'activo' => 'boolean',
    ];

    public function precios()
    {
        return $this->hasMany(Precio::class);
    }
}
