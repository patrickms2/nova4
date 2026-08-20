<?php

namespace App\Models\Taxi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TipoTickets extends Model
{
    use HasFactory;

    protected $table = 'tipos_ticket';

    public $timestamps = false;

    protected $fillable = [
        'nombre',
        'color',
        'estado',
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class, 'tipo_id');
    }
}
