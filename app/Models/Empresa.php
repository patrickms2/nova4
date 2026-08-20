<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = [
        'codeempresa',
        'empresa',
        'nombre',
        'nif',
        'direccion',
        'codigopostal',
        'telefono',
        'fax',
        'web',
        'email',
        'cuentacorriente',
        'tarjetacredito',
        'tipocredito',
        'fechaalta',
        'fechamodificado',
        'fechafacturado',
        'fechabaja',
        'usuario',
        'observaciones',
        'logo_empresa',
        'logopublicidad',
        'administrador',
        'poblacion',
        'porcentajeexplotacion',
    ];

    protected $casts = [
        'fechaalta' => 'date',
        'fechamodificado' => 'date',
        'fechafacturado' => 'date',
        'fechabaja' => 'date',
        'porcentajeexplotacion' => 'float',
    ];

    public function facturas()
    {
        return $this->hasMany(Factura::class);
    }
    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
    public function clientes_facturas()
    {
        return $this->hasMany(Cliente::class)->withCount('facturas');
    }
    public function facturas_clientes()
    {
        return $this->hasMany(Factura::class)->withCount('clientes');
    }
}
