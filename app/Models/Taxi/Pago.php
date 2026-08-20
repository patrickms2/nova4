<?php

declare(strict_types=1);

// app/Models/Pago.php

namespace App\Models\Taxi;

use App\Enums\PagoEstado;
use App\Models\User;
use App\Services\PagoReferenciaService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Pago extends Model
{
    protected $table = 'taxis_pagos';

    protected $fillable = [
        'id', 'usuario_id', 'taxista_id', 'nombre', 'telefono', 'recogida', 'referencia', 'ref_pago', 'email', 'importe', 'total', 'estado_id',
        'personas', 'fecha_servicio', 'fecha_notificado', 'notificado', 'metodo_pago', 'fecha_terminado', 'fecha_alta', 'factura', 'latlng', 'direccion', 'pagado', 'status',
    ];

    protected $casts = [
        'fecha_servicio' => 'datetime',
        'fecha_terminado' => 'datetime',
        'fecha_notificado' => 'datetime',
        'fecha_alta' => 'datetime',
        'personas' => 'integer',
        'notificado' => 'boolean',
        'pagado' => 'float',
        'importe' => 'float',
        'total' => 'float',
        'status' => PagoEstado::class,
        'direccion' => 'string',
        'factura' => 'boolean',
        // 'estado_id' => PagoEstado::class,
        'referencia' => 'string',
        'pagos' => 'array',

    ];

    public function totalPagos()
    {

        return $this->pagos->sum('importe');
    }

    public function pagos()
    {
        return $this->hasMany(PagoServicio::class);
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function taxista(): BelongsTo
    {
        return $this->belongsTo(Taxista::class, 'taxista_id');
    }

    public function getEmployeeIdAttribute(): ?int
    {
        return isset($this->attributes['usuario_id']) ? (int) $this->attributes['usuario_id'] : null;
    }

    public function setEmployeeIdAttribute(?int $value): void
    {
        $this->attributes['usuario_id'] = $value;
    }

    public function getTaxistaUserIdAttribute(): ?int
    {
        return isset($this->attributes['taxista_id']) ? (int) $this->attributes['taxista_id'] : null;
    }

    public function setTaxistaUserIdAttribute(?int $value): void
    {
        $this->attributes['taxista_id'] = $value;
    }

    public function refID(int $id)
    {
        return PagoReferenciaService::buildReferenceFromId($id);
    }

    public function documents()
    {
        return $this->artifacts('documentos');
    }

    public function getNotificadoAttribute($value)
    {
        return $value === 1 ? 'Si' : 'No';
    }

    protected static function booted()
    {

        self::updating(function ($model) {
            $model->fecha_alta = now();
            $model->fecha_notificado = now();
            // $model->estado_id = PagoEstado::PAGO_PARCIAL;
            // $model->estado_id = PagoEstado::PAGO_PARCIAL;

            if ($model->referencia === null) {
                $model->referencia = PagoReferenciaService::buildReferenceFromId($model->id);
            }

            $model->pagado = PagoServicio::where('pago_id', $model->id)->sum('importe');

        });
        self::creating(function ($model) {
            $model->fecha_alta = now();
            $model->fecha_servicio = now();
            $model->fecha_notificado = now();
            $model->factura = false;
            $model->notificado = false;
            $model->status = PagoEstado::PENDIENTE;
            // $model->estado_id = PagoEstado::PENDIENTE;
            $model->metodo_pago = 'Efectivo';
            $model->direccion = '';
            $model->ref_pago = '';
            $model->personas = 1;
            $model->taxista_id = null;
            $model->referencia = PagoReferenciaService::generateUsingNextAutoIncrement();

        });
    }
}
