<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FormaCobro extends Model
{
    /** @use HasFactory<\Database\Factories\FormaCobroFactory> */
    use HasFactory;

    protected $table = 'formas_cobro';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'activa',
        'orden',
    ];

    protected function casts(): array
    {
        return [
            'activa' => 'boolean',
            'orden' => 'integer',
        ];
    }

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }
}
