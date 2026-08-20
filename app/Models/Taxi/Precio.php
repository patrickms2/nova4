<?php
namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Precio extends Model
{
    use HasFactory;

    protected $table = 'precios';

    protected $fillable = [
        'zona_id',
        'destino',
        'precio',
        'observaciones',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];

    public function zona()
    {
        return $this->belongsTo(Zona::class);
    }
}
