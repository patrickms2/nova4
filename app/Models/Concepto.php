<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Concepto extends Model
{
    use HasFactory;

    protected $fillable = [
        'codconcepto',
        'cliente_id',
        'concepto',
        'grupo',
        'unidad',
        'precio',
        'descuento',
        'impuesto',
        'retenciones',
        'unidadminimo',
        'observaciones',
        'codempresa',
        'categoria',
        'recurrente',
    ];

    protected $casts = [
        'recurrente' => 'boolean',
    ];

    public function registros()
    {
        return $this->hasMany(RegistroFactura::class);
    }
    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }
    public static function categorias(): array
    {
        return [
            'alojamiento' => 'Servicio de alojamiento',
            'restauracion' => 'Servicio de restauración',
            'transporte' => 'Servicio de transporte',
            'otros' => 'Otros servicios',
        ];
    }
}
