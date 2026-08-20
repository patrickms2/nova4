<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RentalExpense extends Model
{
    /** @use HasFactory<\Database\Factories\RentalExpenseFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_property_id',
        'category',
        'provider_name',
        'description',
        'base_amount',
        'tax_amount',
        'total_amount',
        'expense_date',
        'status',
        'is_recurrent',
        'document_path',
    ];

    protected $casts = [
        'base_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'expense_date' => 'date',
        'is_recurrent' => 'boolean',
    ];

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(RentalDocument::class, 'documentable');
    }

    public static function categories(): array
    {
        return [
            'seguro' => 'Seguro',
            'ibi' => 'IBI',
            'basura' => 'Basura',
            'agua' => 'Agua',
            'luz' => 'Luz',
            'internet' => 'Internet',
            'jardin' => 'Jardín',
            'piscina' => 'Piscina',
            'jacuzzi' => 'Jacuzzi',
            'amazon' => 'Amazon',
            'reposiciones' => 'Reposiciones',
            'reformas' => 'Reformas',
            'mantenimiento' => 'Mantenimiento',
            'impuestos' => 'Impuestos',
        ];
    }
}
