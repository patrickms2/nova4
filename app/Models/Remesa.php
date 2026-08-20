<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Remesa extends Model
{
    /** @use HasFactory<\Database\Factories\RemesaFactory> */
    use HasFactory;

    protected $fillable = [
        'nombre',
        'fecha',
        'estado',
        'notas',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function clientes(): BelongsToMany
    {
        return $this->belongsToMany(Cliente::class, 'remesa_clientes', 'remesa_id', 'cliente_id')
            ->withTimestamps();
    }

    public function remesaClientes(): HasMany
    {
        return $this->hasMany(RemesaCliente::class, 'remesa_id');
    }

    public function facturas(): HasMany
    {
        return $this->hasMany(Factura::class, 'remesa_id');
    }

    public function isDraft(): bool
    {
        return $this->estado === 'draft';
    }

    public function markAsGenerated(): void
    {
        $this->update(['estado' => 'generated']);
    }
}
