<?php

namespace App\Models;

use Database\Factories\RentalContactFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RentalContact extends Model
{
    /** @use HasFactory<RentalContactFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_property_id',
        'person_id',
        'category',
        'name',
        'phone',
        'email',
        'address',
    ];

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function person(): BelongsTo
    {
        return $this->belongsTo(Person::class);
    }

    public function workCategories(): BelongsToMany
    {
        return $this->belongsToMany(WorkCategory::class, 'rental_contact_work_category')->withTimestamps();
    }

    public static function categories(): array
    {
        return [
            'limpieza' => 'Limpieza',
            'jardineria' => 'Jardinería',
            'piscina' => 'Piscina',
            'fontanero' => 'Fontanero',
            'electricista' => 'Electricista',
            'seguros' => 'Seguros',
            'bayside' => 'Bayside',
            'abogado' => 'Abogado',
            'gestoria' => 'Gestoría',
        ];
    }
}
