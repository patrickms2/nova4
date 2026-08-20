<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Automation extends Model
{
    /** @use HasFactory<\Database\Factories\AutomationFactory> */
    use HasFactory;

    protected $fillable = [
        'property_id',
        'rental_property_id',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(CommunityProperty::class);
    }

    public function rentalProperty(): BelongsTo
    {
        return $this->belongsTo(RentalProperty::class);
    }

    public function conditions(): HasMany
    {
        return $this->hasMany(AutomationCondition::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(AutomationAction::class);
    }
}
