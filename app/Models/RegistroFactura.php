<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroFactura extends Model
{
    use HasFactory;

    protected $table = 'registros_facturas';

    protected $fillable = [
        'factura_id',
        'concepto_id',
        'codfactura',
        'unidad',
        'descripcion',
        'cantidad',
        'precio',
        'descuento',
        'valorimpuesto',
        'valorretenciones',
        'importe',
        'impuesto',
        'retenciones',
        'fecha',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function factura()
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }

    public function concepto()
    {
        return $this->belongsTo(Concepto::class, 'concepto_id');
    }
}
