<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferTariff extends Model
{
    protected $fillable = [
        'origin_zone',
        'destination_zone',
        'price',
        'currency',
        'holiday_surcharge_percent',
        'igic_percent',
        'igic_included',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'holiday_surcharge_percent' => 'integer',
            'igic_percent' => 'integer',
            'igic_included' => 'boolean',
            'is_active' => 'boolean',
        ];
    }



}
