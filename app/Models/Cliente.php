<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Contracts\VeriFactuRecipient;

class Cliente extends Model implements VeriFactuRecipient
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $fillable = [
        'codcliente',
        'codcontabilidad',
        'nombretotal',
        'nombre',
        'apellido',
        'identificacion',
        'dni',
        'tipo',
        'sexo',
        'domicilio',
        'poblacion',
        'codigopostal',
        'provincia',
        'pais',
        'nacionalidad',
        'telefono',
        'fax',
        'movil',
        'trabajo',
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
        'domiciliado',
        'empresa_id',
        'recurrencia_dia',
        'recurrencia_activa',
        'recurrencia_notas',
    ];

    protected $casts = [
        'fechaalta' => 'date',
        'fechamodificado' => 'date',
        'fechafacturado' => 'date',
        'fechabaja' => 'date',
        'domiciliado' => 'boolean',
        'recurrencia_activa' => 'boolean',
        'recurrencia_dia' => 'integer',
    ];

    protected $appends = ['nombreempresa','nombrecorto', 'direccion'];

    public function empresa()
    {
        return $this->belongsTo(Empresa::class,'empresa_id')
                    ->select('id', 'empresa', 'nombre');
    }

    public function facturas()
    {
        return $this->hasMany(Factura::class, 'cliente_id');
    }

    public function conceptos()
    {
        return $this->hasMany(Concepto::class, 'cliente_id')
            ->select('id', 'cliente_id', 'concepto', 'unidad', 'precio', 'descuento', 'impuesto', 'retenciones', 'categoria', 'observaciones');
    }

    public function novaBusiness()
    {
        return $this->belongsTo(NovaBusiness::class, 'email', 'contact_email');
    }

    public function getDireccionAttribute(): string
    {
        return trim(($this->domicilio ?? '').'  '.($this->codigopostal ?? '').' '.($this->poblacion ?? '').', '.($this->provincia ?? ''));
    }

    public function getNombreCortoAttribute(): string
    {
        if ($this->nombretotal) {
            return $this->nombretotal;
        }

        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }
    public function getNombreEmpresaAttribute(): string
    {
     

        return trim($this->empresa()->first()->nombre );
    }
    public function getName(): string
    {
        return $this->nombretotal ?: $this->nombrecorto ?: 'Cliente';
    }

    public function getTaxId(): ?string
    {
        return $this->dni ?: null;
    }
}
