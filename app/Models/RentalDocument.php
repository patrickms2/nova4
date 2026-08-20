<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class RentalDocument extends Model
{
    /** @use HasFactory<\Database\Factories\RentalDocumentFactory> */
    use HasFactory;

    protected $fillable = [
        'documentable_type',
        'documentable_id',
        'category',
        'title',
        'file_path',
        'expiry_date',
        'meta',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'meta' => 'array',
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function categories(): array
    {
        return [
            'registro_turistico' => 'Registro turístico',
            'seguro' => 'Seguro',
            'escrituras' => 'Escrituras',
            'ibi' => 'IBI',
            'catastro' => 'Catastro',
            'contratos' => 'Contratos',
            'facturas' => 'Facturas',
            'licencias' => 'Licencias',
            'inventario' => 'Inventario',
            'garantias' => 'Garantías',
            'manuales' => 'Manuales',
        ];
    }
}
