<?php

// app/Models/Pago.php
namespace App\Models\Taxi;

   
use Illuminate\Database\Eloquent\Model;
use App\Models\Taxi\Usuario;
use App\Enums\PagoEstado;
use App\Services\PagoServicioRefService;

class PagoServicio extends Model
{
    protected $table = 'taxis_pagosservicios';


    protected $fillable = [
        'id','usuario_id','pago_id', 'ref_pago', 'importe', 'metodo_pago','status'
    ];
    protected $casts = [
        'importe' => 'float',
        'status' => PagoEstado::class,
    ];
protected static function booted    ()
{

    static::updating(function ($model) {
        if($model->ref_pago == null) {
            $model->ref_pago = PagoServicioRefService::buildReferenceFromId($model->id);
        }
    });
    static::creating(function ($model) {
        $model->status = PagoEstado::PENDIENTE;
        //$model->estado_id = PagoEstado::PENDIENTE;
        $model->metodo_pago = 'Efectivo';

        });
    }
    public function refID(int $id){
        return PagoServicioRefService::buildReferenceFromId($id);
    }
    public function documents()
    {
        return $this->artifacts('documentos');
    }

    public function getNotificadoAttribute($value)
    {
        return $value == 1 ? 'Si' : 'No';
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
    public function pago()
    {
        return $this->belongsTo(Pago::class, 'pago_id');
    }

}
