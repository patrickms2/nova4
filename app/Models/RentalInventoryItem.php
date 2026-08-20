<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class RentalInventoryItem extends Model
{
    /** @use HasFactory<\Database\Factories\RentalInventoryItemFactory> */
    use HasFactory;

    protected $fillable = [
        'rental_property_id',
        'category',
        'location',
        'brand',
        'model',
        'serial_number',
        'purchase_date',
        'purchase_value',
        'warranty_expires_at',
        'status',
        'qr_code',
        'photo_path',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'warranty_expires_at' => 'date',
        'purchase_value' => 'decimal:2',
    ];

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function incidents(): MorphMany
    {
        return $this->morphMany(RentalIncident::class, 'subject');
    }

    public function isUnderWarranty(): bool
    {
        return $this->warranty_expires_at && $this->warranty_expires_at->isFuture();
    }
}
