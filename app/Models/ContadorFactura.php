<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContadorFactura extends Model
{
    protected $table = 'contador_facturas';

    protected $fillable = [
        'contador',
        'ano',
    ];

    public $timestamps = false;

    protected $casts = [
        'ano' => 'integer',
        'contador' => 'integer',
    ];
}
