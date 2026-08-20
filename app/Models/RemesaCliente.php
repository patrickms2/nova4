<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RemesaCliente extends Model
{
    /** @use HasFactory<\Database\Factories\RemesaClienteFactory> */
    use HasFactory;

    protected $table = 'remesa_clientes';

    protected $fillable = [
        'remesa_id',
        'cliente_id',
        'factura_id',
    ];

    public function remesa(): BelongsTo
    {
        return $this->belongsTo(Remesa::class, 'remesa_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    public function factura(): BelongsTo
    {
        return $this->belongsTo(Factura::class, 'factura_id');
    }
}
